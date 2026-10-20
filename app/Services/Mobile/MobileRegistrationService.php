<?php

namespace App\Services\Mobile;

use App\Models\Customer;
use App\Models\PassengerAccount;
use App\Models\PendingPassengerRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MobileRegistrationService
{
    public function start(array $input, int $company): PendingPassengerRegistration
    {
        MobilePassengerControls::assertRegistration($company, $input['mobile'], $input['cnic']);
        $this->assertNumberAvailable($company, $input['mobile']);
        return PendingPassengerRegistration::create([
            'company_id' => $company, 'mobile' => $input['mobile'],
            'full_name' => $input['full_name'], 'cnic' => $input['cnic'],
            'email' => $input['email'] ?? null, 'password' => Hash::make($input['password']),
            'expires_at' => now()->addHours(max(1, (int) config('mobile.registration_ttl_hours', 24))),
        ]);
    }

    public function send(PendingPassengerRegistration $pending, MobileOtpService $otp): void
    {
        $otp->ensureConfigured();
        $code = (string) random_int(100000, 999999);
        DB::transaction(function () use ($pending, $code) {
            $fresh = PendingPassengerRegistration::whereKey($pending->id)->lockForUpdate()->firstOrFail();
            $this->assertActive($fresh);
            $this->assertNumberAvailable((int) $fresh->company_id, $fresh->mobile);
            MobilePassengerControls::assertRegistration((int) $fresh->company_id, $fresh->mobile, $fresh->cnic);
            MobilePassengerControls::assertOtp(null, (int) $fresh->company_id);
            $fresh->forceFill(['otp_digest' => Hash::make($code), 'otp_attempts' => 0,
                'otp_expires_at' => now()->addMinutes((int) config('mobile.otp.ttl_minutes', 10))->min($fresh->expires_at)])->save();
        });
        $otp->deliverRegistrationCode($pending->mobile, $pending->id, $code);
    }

    public function complete(PendingPassengerRegistration $pending, string $code): ?array
    {
        return DB::transaction(function () use ($pending, $code) {
            $pending = PendingPassengerRegistration::whereKey($pending->id)->lockForUpdate()->first();
            if (!$pending) { throw new RuntimeException('Registration expired. Please sign up again.', 401); }
            $this->assertActive($pending);
            if (!$pending->otp_digest || !$pending->otp_expires_at || $pending->otp_expires_at->lte(now())) { return null; }
            if (!Hash::check($code, $pending->otp_digest)) {
                $pending->otp_attempts++;
                if ($pending->otp_attempts >= 5) { $pending->otp_digest = null; $pending->otp_expires_at = null; }
                $pending->save();
                return null;
            }
            MobilePassengerControls::assertRegistration((int) $pending->company_id, $pending->mobile, $pending->cnic);
            MobilePassengerControls::assertOtp(null, (int) $pending->company_id);
            $this->assertNumberAvailable((int) $pending->company_id, $pending->mobile);
            // Customer and account creation/linking happen only after a valid OTP.
            $customer = Customer::where('company_id', $pending->company_id)
                ->whereIn('contact', MobilePassengerControls::mobileVariants($pending->mobile))->lockForUpdate()->first();
            if (!$customer) {
                $customer = Customer::create(['company_id' => $pending->company_id, 'contact' => $pending->mobile,
                    'name' => $pending->full_name, 'cnic' => $pending->cnic]);
            }
            $account = PassengerAccount::create([
                'company_id' => $pending->company_id, 'customer_id' => $customer->id,
                'mobile' => $pending->mobile, 'email' => $pending->email,
                'password' => $pending->password, 'mobile_verified_at' => now(),
            ]);
            MobilePassengerControls::assertAccount($account);
            $token = $account->createToken('passenger-mobile')->plainTextToken;
            app(MobileAppTelemetry::class)->recordLogin($account, 'signup');
            $pending->tokens()->delete();
            $pending->delete();
            return ['account' => $account->load('customer'), 'token' => $token];
        });
    }

    public function assertActive(PendingPassengerRegistration $pending): void
    {
        if ((int) $pending->company_id !== (int) config('mobile.company_id') || $pending->expires_at->lte(now())) {
            throw new RuntimeException('Registration expired. Please sign up again.', 401);
        }
    }

    private function assertNumberAvailable(int $company, string $mobile): void
    {
        if (PassengerAccount::where('company_id', $company)
            ->whereIn('mobile', MobilePassengerControls::mobileVariants($mobile))->exists()) {
            throw new RuntimeException('Unable to register this number. Try signing in or recovering your account.', 422);
        }
    }
}
