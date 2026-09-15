<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\SavePassengerRequest;
use App\Http\Resources\Mobile\SavedPassengerResource;
use App\Models\SavedPassenger;
use Illuminate\Http\Request;

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
        $account = $request->user();
        $existing = SavedPassenger::query()
            ->where('passenger_account_id', $account->id)
            ->where('cnic', $request->cnic)
            ->first();
        $item = SavedPassenger::updateOrCreate(
            [
                'passenger_account_id' => $account->id,
                'cnic' => $request->cnic,
            ],
            [
                'company_id' => $account->company_id,
                'full_name' => $request->full_name,
                'mobile' => $request->mobile,
                'gender' => $request->gender,
            ]
        );

        return $this->success(
            (new SavedPassengerResource($item))->resolve(),
            $existing ? 'Saved passenger updated.' : 'Passenger saved.',
            $existing ? 200 : 201
        );
    }

    public function update(SavePassengerRequest $request, $savedPassenger)
    {
        $item = $this->findOwned($request, $savedPassenger);
        $item->update($request->only(['full_name', 'cnic', 'mobile', 'gender']));

        return $this->success(
            (new SavedPassengerResource($item->fresh()))->resolve(),
            'Saved passenger updated.'
        );
    }

    public function destroy(Request $request, $savedPassenger)
    {
        $this->findOwned($request, $savedPassenger)->delete();
        return $this->success(null, 'Saved passenger removed.');
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
