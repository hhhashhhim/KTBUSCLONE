<?php

namespace App\Http\Middleware;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobilePassengerControls as Controls;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;

class EnforceMobilePassengerControls
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $company = (int) config('mobile.company_id');
            $policy = Controls::policy($company);
            $publicAuth = $request->is('api/mobile/v1/auth/register', 'api/mobile/v1/auth/login',
                'api/mobile/v1/auth/forgot-password', 'api/mobile/v1/auth/verify-reset-otp', 'api/mobile/v1/auth/reset-password');
            $account = $publicAuth ? Controls::requestAccount($request) : $request->user();
            $reset = $request->is('api/mobile/v1/auth/forgot-password', 'api/mobile/v1/auth/verify-reset-otp', 'api/mobile/v1/auth/reset-password');
            if ($account instanceof PassengerAccount) { Controls::assertAccount($account, $reset); }
            if ($request->is('api/mobile/v1/auth/register')) {
                $mobile = $request->input('mobile'); $cnic = $request->input('cnic');
                Controls::assertRegistration($company, is_string($mobile) ? $mobile : '', is_string($cnic) ? $cnic : null);
            }
            if ($request->is('api/mobile/v1/auth/login') && !$policy['logins_enabled']) {
                throw new RuntimeException('Mobile sign-in is temporarily unavailable.', 403);
            }
            if ($reset || $request->is('api/mobile/v1/auth/register', 'api/mobile/v1/auth/resend-otp', 'api/mobile/v1/auth/verify-otp')) {
                Controls::assertOtp($account instanceof PassengerAccount ? $account : null, $company);
            }
            return $next($request);
        } catch (RuntimeException $exception) {
            if (!in_array($exception->getCode(), [403, 429], true)) { throw $exception; }
            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'data' => null, 'errors' => (object) []], $exception->getCode());
        }
    }
}
