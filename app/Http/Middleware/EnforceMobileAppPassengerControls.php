<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Sanctum\Sanctum;

class EnforceMobileAppPassengerControls
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->is('api/mobile/v1/*') || !Schema::hasTable('mobile_app_passenger_controls')) {
            return $next($request);
        }
        $accountId = null;
        $companyId = null;
        if ($request->is('api/mobile/v1/auth/login') && $request->isMethod('post') && Schema::hasTable('passenger_accounts')) {
            $companyId = (int) config('mobile.company_id', env('MOBILE_COMPANY_ID'));
            $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile'));
            if ($companyId > 0 && $mobile !== '') {
                $accountId = DB::table('passenger_accounts')->where('company_id', $companyId)->where('mobile', $mobile)->value('id');
            }
        } elseif ($request->bearerToken() && Schema::hasTable('personal_access_tokens') && Schema::hasTable('passenger_accounts')) {
            // Run before route authentication without resolving the passenger model,
            // which belongs to the separately deployed mobile backend.
            $tokenClass = Sanctum::personalAccessTokenModel();
            $token = $tokenClass::findToken($request->bearerToken());
            $tokenType = $token ? (Relation::getMorphedModel($token->tokenable_type) ?: $token->tokenable_type) : null;
            if ($token && $tokenType === 'App\\Models\\PassengerAccount') {
                $account = DB::table('passenger_accounts')->where('id', $token->tokenable_id)->first(['id', 'company_id']);
                if ($account) { $accountId = $account->id; $companyId = $account->company_id; }
            }
        }
        if ($accountId && DB::table('mobile_app_passenger_controls')->where('company_id', $companyId)
            ->where('passenger_account_id', $accountId)->where('status', '!=', 'active')->exists()) {
            return response()->json(['success' => false, 'message' => 'Your mobile account is suspended. Please contact support.',
                'data' => null, 'errors' => (object) ['account' => ['Your mobile account is suspended.']]], 403);
        }
        return $next($request);
    }
}
