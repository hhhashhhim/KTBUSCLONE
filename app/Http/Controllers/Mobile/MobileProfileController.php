<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\UpdatePassengerProfileRequest;
use App\Http\Resources\Mobile\PassengerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileProfileController extends Controller
{
    use RespondsWithMobileApi;

    public function show(Request $request)
    {
        $account = $request->user()->load('customer');
        return $this->success((new PassengerResource($account))->resolve(), 'Passenger profile retrieved.');
    }

    public function update(UpdatePassengerProfileRequest $request)
    {
        $account = $request->user();
        DB::transaction(function () use ($request, $account) {
            $account->update($request->only(['email', 'gender']));
            $account->customer->update(array_filter([
                'name' => $request->input('full_name'),
                'cnic' => $request->input('cnic'),
            ], function ($value) {
                return !is_null($value);
            }));
        });

        return $this->success(
            (new PassengerResource($account->fresh('customer')))->resolve(),
            'Passenger profile updated.'
        );
    }
}
