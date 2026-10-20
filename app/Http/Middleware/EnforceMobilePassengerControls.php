<?php

namespace App\Http\Middleware;

use App\Models\PassengerAccount;
use App\Models\PendingPassengerRegistration;
use App\Services\Mobile\MobileRegistrationService;
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
            if (!$publicAuth) {
                if (!$account instanceof PassengerAccount && !$account instanceof PendingPassengerRegistration) {
                    throw new RuntimeException('Passenger sign-in required.', 403);
                }
                if ((int) $account->company_id !== $company) {
                    throw new RuntimeException('Passenger sign-in required.', 403);
                }
                $verificationRoute = $request->is('api/mobile/v1/auth/session', 'api/mobile/v1/auth/logout',
                    'api/mobile/v1/auth/registration', 'api/mobile/v1/auth/resend-otp', 'api/mobile/v1/auth/verify-otp');
                if ($account instanceof PendingPassengerRegistration) {
                    app(MobileRegistrationService::class)->assertActive($account);
                    if (!$verificationRoute || !$account->tokenCan('registration:verify')) {
                        throw new RuntimeException('Verify your mobile number before continuing.', 403);
                    }
                } elseif (!$account->mobile_verified_at && !$verificationRoute) {
                    // Includes tokens issued by the old registration flow.
                    throw new RuntimeException('Verify your mobile number before continuing.', 403);
                }
            }
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
            if (!in_array($exception->getCode(), [401, 403, 429], true)) { throw $exception; }
            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'data' => null, 'errors' => (object) []], $exception->getCode());
        }
    }
}
