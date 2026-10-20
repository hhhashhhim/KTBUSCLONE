<?php

namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class MobileBiometricSessions
{
    private const INVALID = 'Quick login has expired or was revoked. Sign in with your password.';

    public function enroll(PassengerAccount $account, $accessToken, string $password): array
    {
        $this->available();
        return DB::transaction(function () use ($account, $accessToken, $password) {
            $account = $this->account($account->id);
            $this->assertAllowed($account);
            $this->limitAttempts($account, 'enroll');
            if (!Hash::check($password, $account->password)) {
                throw new RuntimeException('The password is incorrect.', 422);
            }
            $token = $accessToken instanceof PersonalAccessToken
                ? $account->tokens()->whereKey($accessToken->id)->lockForUpdate()->first() : null;
            if (!$token) { throw new RuntimeException(self::INVALID, 401); }
            $this->removeForToken($token->id);
            $expires = now()->addDays(max(1, min(90, (int) config('mobile.biometric_session_days', 30))));
            $session = DB::table('mobile_biometric_sessions')->insertGetId([
                'company_id' => $account->company_id, 'passenger_account_id' => $account->id,
                'access_token_id' => $token->id, 'expires_at' => $expires,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return ['refresh_token' => $this->issue($session), 'expires_at' => $expires->toIso8601String()];
        });
    }

    public function renew(string $secret): array
    {
        $this->available();
        $hash = hash('sha256', $secret);
        $found = DB::table('mobile_biometric_tokens as t')
            ->join('mobile_biometric_sessions as s', 's.id', '=', 't.session_id')
            ->where('t.token_hash', $hash)->where('s.company_id', (int) config('mobile.company_id'))
            ->select('s.*')->first();
        if (!$found) { throw new RuntimeException(self::INVALID, 401); }

        // Lock in the same order as password login/reset and ERP passenger controls.
        $result = DB::transaction(function () use ($found, $hash) {
            $account = $this->account($found->passenger_account_id);
            $access = $account->tokens()->whereKey($found->access_token_id)->lockForUpdate()->first();
            $session = DB::table('mobile_biometric_sessions')->where('id', $found->id)->lockForUpdate()->first();
            $refresh = DB::table('mobile_biometric_tokens')->where('token_hash', $hash)->lockForUpdate()->first();
            if (!$access || !$session || !$refresh || Carbon::parse($session->expires_at)->lte(now())) {
                $this->removeForToken($found->access_token_id);
                return null;
            }
            // Commit revocation before returning an error: replay must not roll it back.
            if ($refresh->used_at) {
                $access->delete();
                $this->removeForToken($access->id);
                return null;
            }
            $this->assertAllowed($account);
            $this->limitAttempts($account, 'renew');
            DB::table('mobile_biometric_tokens')->where('id', $refresh->id)->update(['used_at' => now()]);
            $plain = bin2hex(random_bytes(32));
            // Keep the ERP-visible token ID stable. Rotation and ERP deletion serialize
            // on this row; a revoked token is never recreated by renewal.
            $access->forceFill(['token' => hash('sha256', $plain), 'created_at' => now(), 'last_used_at' => now()])->save();
            DB::table('mobile_biometric_sessions')->where('id', $session->id)->update(['updated_at' => now()]);
            app(MobileAppTelemetry::class)->recordLogin($account, 'biometric');
            return [
                'token' => $access->id . '|' . $plain,
                'refresh_token' => $this->issue($session->id),
                'expires_at' => Carbon::parse($session->expires_at)->toIso8601String(),
                'passenger' => $account->load('customer'),
            ];
        });
        if (!$result) { throw new RuntimeException(self::INVALID, 401); }
        return $result;
    }

    public function disable(PassengerAccount $account, $accessToken): void
    {
        $this->available();
        DB::transaction(function () use ($account, $accessToken) {
            $account = $this->account($account->id);
            $token = $accessToken instanceof PersonalAccessToken
                ? $account->tokens()->whereKey($accessToken->id)->lockForUpdate()->first() : null;
            if ($token) { $this->removeForToken($token->id); }
        });
    }

    private function available(): void
    {
        if (!config('mobile.biometric_login_enabled', true) || !Schema::hasTable('mobile_biometric_tokens')) {
            throw new RuntimeException('Quick login is not available yet. Please use your password.', 503);
        }
    }

    private function account(int $id): PassengerAccount
    {
        $account = PassengerAccount::whereKey($id)->where('company_id', (int) config('mobile.company_id'))
            ->lockForUpdate()->first();
        if (!$account) { throw new RuntimeException(self::INVALID, 401); }
        return $account;
    }

    private function assertAllowed(PassengerAccount $account): void
    {
        MobilePassengerControls::assertAccount($account);
        if (!$account->mobile_verified_at) {
            throw new RuntimeException('Verify your mobile number before using quick login.', 403);
        }
        if (!MobilePassengerControls::policy((int) $account->company_id)['logins_enabled']) {
            throw new RuntimeException('Mobile sign-in is temporarily unavailable.', 403);
        }
    }

    private function limitAttempts(PassengerAccount $account, string $action): void
    {
        $key = 'mobile-biometric:' . $action . ':' . $account->company_id . ':' . $account->id;
        $limit = (int) MobilePassengerControls::policy((int) $account->company_id)['login_per_minute'];
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new RuntimeException('Too many quick login attempts. Please try again in a minute.', 429);
        }
        RateLimiter::hit($key, 60);
    }

    private function issue(int $session): string
    {
        $secret = bin2hex(random_bytes(48));
        DB::table('mobile_biometric_tokens')->insert([
            'session_id' => $session, 'token_hash' => hash('sha256', $secret), 'created_at' => now(),
        ]);
        return $secret;
    }

    private function removeForToken(int $token): void
    {
        $ids = DB::table('mobile_biometric_sessions')->where('access_token_id', $token)->pluck('id');
        DB::table('mobile_biometric_tokens')->whereIn('session_id', $ids)->delete();
        DB::table('mobile_biometric_sessions')->whereIn('id', $ids)->delete();
    }
}
