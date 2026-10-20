<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileAppAdminData;
use App\Services\MobileAppWallet;
use App\Support\MobileAppScope as Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAppWalletController extends Controller
{
    private $data;
    private $wallet;

    public function __construct(MobileAppAdminData $data, MobileAppWallet $wallet)
    {
        $this->data = $data;
        $this->wallet = $wallet;
    }

    private function access(Request $request, int $id, bool $write = false): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->company_id, 403, 'ERP staff access is required.');
        foreach (['mobile-app', 'mobile-app-view-users'] as $permission) {
            abort_unless(Scope::allows($user, $permission), 403, 'Mobile App access denied.');
        }
        if ($write) {
            abort_unless(Scope::allows($user, 'mobile-app-manage-wallet') && $this->data->accountControlScope($request),
                403, 'Wallet adjustments require their role privilege and company Admin access or all company terminals and routes.');
            $this->data->requireCapability('audit');
        }
        // Do not let submitted balance fields alter the passenger visibility check.
        $scope = Request::create('/', 'GET');
        $scope->setUserResolver($request->getUserResolver());
        abort_unless($this->data->users($scope, false)->where('a.id', $id)->exists(), 404, 'Passenger account not found.');
    }

    private function account(Request $request, int $id, bool $lock = false)
    {
        $query = DB::table('passenger_accounts')->where('company_id', $request->user()->company_id)->where('id', $id);
        if ($lock) { $query->lockForUpdate(); }
        $account = $query->first();
        abort_unless($account, 404, 'Passenger account not found.');
        return $account;
    }

    public function show(Request $request, int $id)
    {
        $this->access($request, $id);
        return response()->json(['status' => true, 'data' => $this->wallet->state($this->account($request, $id))]);
    }

    public function update(Request $request, int $id)
    {
        $this->access($request, $id, true);
        $request->validate([
            'points_available' => 'required|integer|min:0|max:' . MobileAppWallet::MAX_POINTS,
            'revision' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'reason' => 'required|string|max:1000',
        ]);
        $reason = trim($request->input('reason'));
        abort_if(mb_strlen($reason) < 3, 422, 'Enter a reason with at least 3 characters.');

        return DB::transaction(function () use ($request, $id, $reason) {
            // Same company/config order as App Settings; disabling the wallet cannot race this write.
            abort_unless(DB::table('companies')->where('id', $request->user()->company_id)->lockForUpdate()->first(),
                404, 'Company not found.');
            $account = $this->account($request, $id, true);
            $state = $this->wallet->state($account, true);
            abort_unless($state['available'], 503, $state['message']);
            abort_unless($state['enabled'] && $state['active'], 409, $state['message']);
            abort_unless(hash_equals($state['revision'], $request->input('revision')),
                409, 'The wallet changed. Reload the current balance before saving.');
            $points = (int) $request->input('points_available');
            abort_if($points === $state['points_available'], 422, 'Enter a different points balance.');
            DB::table('card_assigns')->where('id', $state['card_id'])->where('company_id', $account->company_id)
                ->where('customer_id', $account->customer_id)->update([
                    'starting_points' => $points, 'updated_by' => $request->user()->id, 'updated_at' => now(),
                ]);
            $this->data->audit($request, 'wallet_points_adjusted', 'passenger', $id, $reason, [
                'card_id' => $state['card_id'], 'customer_id' => (int) $account->customer_id,
                'old_points' => $state['points_available'], 'new_points' => $points,
                'points_change' => $points - $state['points_available'],
            ]);
            return response()->json(['status' => true, 'data' => $this->wallet->state($account)]);
        });
    }
}
