<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\LoginPassengerRequest;
use App\Http\Requests\Mobile\RegisterPassengerRequest;
use App\Http\Requests\Mobile\RequestPasswordResetRequest;
use App\Http\Requests\Mobile\ResetPassengerPasswordRequest;
use App\Http\Requests\Mobile\VerifyPassengerOtpRequest;
use App\Http\Requests\Mobile\VerifyPasswordResetOtpRequest;
use App\Http\Resources\Mobile\PassengerResource;
use App\Models\PendingPassengerRegistration;
use App\Models\PassengerAccount;
use App\Services\Mobile\MobileOtpService;
use App\Services\Mobile\MobileRegistrationService;
use App\Services\Mobile\MobilePassengerControls as Controls;
use App\Services\Mobile\MobileTravelService;
use App\Services\Mobile\MobileAppTelemetry;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MobileAuthController extends Controller
{
    use RespondsWithMobileApi;

    public function register(
        RegisterPassengerRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp,
        MobileRegistrationService $registrations
    ) {
        try {
            $pending = $registrations->start($request->validated(), $travel->companyId());
            $sent = false;
            $message = null;
            try {
                $registrations->send($pending, $otp);
                $sent = true;
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
            }
            return $this->success([
                'passenger' => $pending->passengerSummary(),
                'token' => $pending->createToken('passenger-registration', ['registration:verify'])->plainTextToken,
                'verification_pending' => true,
                'verification_delivery_sent' => $sent,
                'verification_message' => $message,
                'registration_expires_at' => $pending->expires_at->toIso8601String(),
            ], 'Verify your mobile number to complete registration.', 201);
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function session(Request $request)
    {
        $account = $request->user();
        return $this->success([
            'passenger' => $account instanceof PendingPassengerRegistration
                ? $account->passengerSummary() : (new PassengerResource($account->load('customer')))->resolve(),
            'verification_pending' => $account instanceof PendingPassengerRegistration || !$account->mobile_verified_at,
        ]);
    }

    public function cancelRegistration(Request $request)
    {
        $pending = $request->user();
        if (!$pending instanceof PendingPassengerRegistration) {
            return $this->failure('No pending registration.', [], 403);
        }
        DB::transaction(function () use ($pending) {
            $pending = PendingPassengerRegistration::whereKey($pending->id)->lockForUpdate()->first();
            if ($pending) { $pending->tokens()->delete(); $pending->delete(); }
        });
        return $this->success(null, 'Registration cancelled.');
    }

    public function login(LoginPassengerRequest $request, MobileTravelService $travel)
    {
        try {
            return DB::transaction(function () use ($request, $travel) {
                $account = PassengerAccount::with('customer')
                    ->where('company_id', $travel->companyId())
                    ->whereIn('mobile', Controls::mobileVariants($request->mobile))
                    ->lockForUpdate()->first();
                if (!$account || !Hash::check($request->password, $account->password)) {
                    return $this->failure('The mobile number or password is incorrect.', [], 401);
                }
                Controls::assertAccount($account);
                if (!Controls::policy((int) $account->company_id)['logins_enabled']) { throw new RuntimeException('Mobile sign-in is temporarily unavailable.', 403); }
                $account->tokens()->delete();
                $token = $account->createToken('passenger-mobile', $account->mobile_verified_at ? ['*'] : ['registration:verify'])->plainTextToken;
                app(MobileAppTelemetry::class)->recordLogin($account, 'login');

                return $this->success([
                    'passenger' => (new PassengerResource($account))->resolve(),
                    'token' => $token,
                ], 'Signed in successfully.');
            });
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function logout(Request $request)
    {
        optional($request->user()->currentAccessToken())->delete();
        return $this->success(null, 'Signed out successfully.');
    }

    public function resendOtp(Request $request, MobileOtpService $otp, MobileRegistrationService $registrations)
    {
        try {
            if ($request->user() instanceof PendingPassengerRegistration) {
                $registrations->send($request->user(), $otp);
            } else {
                $otp->issue($request->user());
            }
            return $this->success(null, 'Verification code sent.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 503);
        }
    }

    public function verifyOtp(VerifyPassengerOtpRequest $request, MobileOtpService $otp, MobileRegistrationService $registrations)
    {
        try {
            if ($request->user() instanceof PendingPassengerRegistration) {
                $result = $registrations->complete($request->user(), $request->code);
            } else {
                // Old unverified accounts must also rotate ALL pre-verification tokens.
                $result = DB::transaction(function () use ($request, $otp) {
                    $account = $request->user();
                    if (!$otp->verify($account, $request->code)) { return null; }
                    $account->tokens()->delete();
                    return ['account' => $account->load('customer'),
                        'token' => $account->createToken('passenger-mobile')->plainTextToken];
                });
            }
            if (!$result) { return $this->failure('The verification code is invalid or expired.', [], 422); }
            return $this->success([
                'passenger' => (new PassengerResource($result['account']))->resolve(),
                'token' => $result['token'], 'verification_pending' => false,
            ], 'Mobile number verified.');
        } catch (QueryException $exception) {
            // Concurrent completions still obey the unique company/mobile and customer constraints.
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return $this->failure('Unable to complete registration. Try signing in or recovering your account.', [], 409);
            }
            report($exception);
            return $this->failure('Something went wrong. Please try again.', [], 500);
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function forgotPassword(
        RequestPasswordResetRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp
    ) {
        try {
            $otp->ensureConfigured();
            $account = PassengerAccount::where('company_id', $travel->companyId())
                ->whereIn('mobile', Controls::mobileVariants($request->mobile))
                ->first();
            if ($account) {
                $otp->issuePasswordReset($account);
            }
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $this->exceptionStatus($exception, 503));
        }

        return $this->success(null, 'If an account exists, a verification code has been sent.', 202);
    }

    public function verifyPasswordResetOtp(
        VerifyPasswordResetOtpRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp
    ) {
        $account = PassengerAccount::where('company_id', $travel->companyId())
            ->whereIn('mobile', Controls::mobileVariants($request->mobile))
            ->first();
        $token = $account ? $otp->verifyPasswordReset($account, $request->code) : null;
        if (!$token) {
            return $this->failure('The verification code is invalid or expired.', [], 422);
        }

        return $this->success(['reset_token' => $token], 'Verification code accepted.');
    }

    public function resetPassword(
        ResetPassengerPasswordRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp
    ) {
        $account = PassengerAccount::where('company_id', $travel->companyId())
            ->whereIn('mobile', Controls::mobileVariants($request->mobile))
            ->first();
        if (!$account || !$otp->resetPassword($account, $request->reset_token, $request->password)) {
            return $this->failure('The password reset session is invalid or expired.', [], 422);
        }

        return $this->success(null, 'Password updated successfully.');
    }
}
