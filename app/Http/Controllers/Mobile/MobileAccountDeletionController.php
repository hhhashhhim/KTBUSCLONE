<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\DeletePassengerAccountRequest;
use App\Models\MobileBookingQuote;
use App\Models\PassengerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MobileAccountDeletionController extends Controller
{
    use RespondsWithMobileApi;

    public function destroy(DeletePassengerAccountRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $account = PassengerAccount::whereKey($request->user()->id)
                ->where('company_id', $request->user()->company_id)
                ->lockForUpdate()->first();
            if (!$account) {
                return $this->failure('Your session expired. Please sign in again.', [], 401);
            }
            if (!Hash::check($request->password, $account->password)) {
                return $this->failure('The password is incorrect.', [
                    'password' => ['Enter your current password.'],
                ], 422);
            }

            // Keep an audit event without copying contact details or credentials.
            DB::table('mobile_account_deletions')->insert([
                'passenger_account_id' => $account->id,
                'company_id' => $account->company_id,
                'deleted_at' => now(),
            ]);
            $account->savedPassengers()->delete();
            MobileBookingQuote::where('passenger_account_id', $account->id)->delete();
            $account->tokens()->delete();
            $account->delete();

            // Shared ERP customer, ticket, payment and loyalty records remain intact.
            return $this->success(null, 'Your mobile account has been deleted.');
        });
    }
}
