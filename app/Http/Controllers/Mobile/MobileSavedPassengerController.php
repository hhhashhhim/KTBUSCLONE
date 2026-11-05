<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\SavePassengerRequest;
use App\Http\Resources\Mobile\SavedPassengerResource;
use App\Models\PassengerAccount;
use App\Models\SavedPassenger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSavedPassengerController extends Controller
{
    use RespondsWithMobileApi;

    public function index(Request $request)
    {
        $account = $request->user();
        $items = SavedPassenger::query()
            ->where('passenger_account_id', $account->id)
            ->where('company_id', $account->company_id)
            ->orderBy('full_name')
            ->get();

        return $this->success(
            SavedPassengerResource::collection($items)->resolve(),
            'Saved passengers retrieved.'
        );
    }

    public function store(SavePassengerRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $account = $this->lockAccount($request);
            $item = SavedPassenger::updateOrCreate(
                [
                    'passenger_account_id' => $account->id,
                    'company_id' => $account->company_id,
                    'cnic' => $request->cnic,
                ],
                $request->only(['full_name', 'mobile', 'gender'])
            );

            return $this->success(
                (new SavedPassengerResource($item))->resolve(),
                $item->wasRecentlyCreated ? 'Passenger saved.' : 'Saved passenger updated.',
                $item->wasRecentlyCreated ? 201 : 200
            );
        });
    }

    public function update(SavePassengerRequest $request, $savedPassenger)
    {
        return DB::transaction(function () use ($request, $savedPassenger) {
            $this->lockAccount($request);
            $item = $this->findOwned($request, $savedPassenger);
            $item->update($request->only(['full_name', 'cnic', 'mobile', 'gender']));

            return $this->success(
                (new SavedPassengerResource($item->fresh()))->resolve(),
                'Saved passenger updated.'
            );
        });
    }

    public function destroy(Request $request, $savedPassenger)
    {
        return DB::transaction(function () use ($request, $savedPassenger) {
            $this->lockAccount($request);
            $this->findOwned($request, $savedPassenger)->delete();
            return $this->success(null, 'Saved passenger removed.');
        });
    }

    private function lockAccount(Request $request): PassengerAccount
    {
        $account = PassengerAccount::where('company_id', $request->user()->company_id)
            ->whereKey($request->user()->id)->lockForUpdate()->first();
        abort_unless($account, 401);
        return $account;
    }

    private function findOwned(Request $request, $id): SavedPassenger
    {
        $account = $request->user();
        return SavedPassenger::query()
            ->where('passenger_account_id', $account->id)
            ->where('company_id', $account->company_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
