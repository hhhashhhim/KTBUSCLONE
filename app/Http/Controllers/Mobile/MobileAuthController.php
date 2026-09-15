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
use App\Models\Customer;
use App\Models\PassengerAccount;
use App\Services\Mobile\MobileOtpService;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MobileAuthController extends Controller
{
    use RespondsWithMobileApi;

    public function register(
        RegisterPassengerRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp
    ) {
        try {
            $companyId = $travel->companyId();
            $account = DB::transaction(function () use ($request, $companyId) {
                $customer = Customer::firstOrNew([
                    'company_id' => $companyId,
                    'contact' => $request->mobile,
                ]);
                if (!$customer->exists) {
                    $customer->fill([
                        'name' => $request->full_name,
                        'cnic' => $request->cnic,
                    ])->save();
                }

                return PassengerAccount::create([
                    'customer_id' => $customer->id,
                    'company_id' => $companyId,
                    'mobile' => $request->mobile,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            });
            $verificationDeliverySent = false;
            $verificationMessage = null;
            try {
                $otp->issue($account);
                $verificationDeliverySent = true;
            } catch (RuntimeException $exception) {
                // Keep the account and token so the passenger can retry delivery.
                $verificationMessage = $exception->getMessage();
            }
            $account->load('customer');

            return $this->success([
                'passenger' => (new PassengerResource($account))->resolve(),
                'token' => $account->createToken('passenger-mobile')->plainTextToken,
                'verification_pending' => true,
                'verification_delivery_sent' => $verificationDeliverySent,
                'verification_message' => $verificationMessage,
            ], 'Passenger account created.', 201);
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function login(LoginPassengerRequest $request, MobileTravelService $travel)
    {
        try {
            $account = PassengerAccount::with('customer')
                ->where('company_id', $travel->companyId())
                ->where('mobile', $request->mobile)
                ->first();
            if (!$account || !Hash::check($request->password, $account->password)) {
                return $this->failure('The mobile number or password is incorrect.', [], 401);
            }
            $account->tokens()->delete();

            return $this->success([
                'passenger' => (new PassengerResource($account))->resolve(),
                'token' => $account->createToken('passenger-mobile')->plainTextToken,
            ], 'Signed in successfully.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function logout(Request $request)
    {
        optional($request->user()->currentAccessToken())->delete();
        return $this->success(null, 'Signed out successfully.');
    }

    public function resendOtp(Request $request, MobileOtpService $otp)
    {
        try {
            $otp->issue($request->user());
            return $this->success(null, 'Verification code sent.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 503);
        }
    }

    public function verifyOtp(VerifyPassengerOtpRequest $request, MobileOtpService $otp)
    {
        if (!$otp->verify($request->user(), $request->code)) {
            return $this->failure('The verification code is invalid or expired.', [], 422);
        }
        return $this->success([
            'passenger' => (new PassengerResource($request->user()->load('customer')))->resolve(),
        ], 'Mobile number verified.');
    }

    public function forgotPassword(
        RequestPasswordResetRequest $request,
        MobileTravelService $travel,
        MobileOtpService $otp
    ) {
        try {
            $otp->ensureConfigured();
            $account = PassengerAccount::where('company_id', $travel->companyId())
                ->where('mobile', $request->mobile)
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
            ->where('mobile', $request->mobile)
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
            ->where('mobile', $request->mobile)
            ->first();
        if (!$account || !$otp->resetPassword($account, $request->reset_token, $request->password)) {
            return $this->failure('The password reset session is invalid or expired.', [], 422);
        }

        return $this->success(null, 'Password updated successfully.');
    }
}
