<?php

namespace App\Providers;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobilePassengerControls as Controls;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
          return Limit::perMinute(500)->by(optional($request->user())->id ?: $request->ip());
        });

        // These limits apply only to the mobile passenger authentication routes.
        // Share account budgets across IP addresses and OTP delivery endpoints.
        $accountKey = function (Request $request) {
            $passenger = $request->user();
            $authenticatedOtp = $passenger instanceof PassengerAccount && $request->is(
                'api/mobile/v1/auth/resend-otp', 'api/mobile/v1/auth/verify-otp'
            );
            $mobile = $authenticatedOtp ? $passenger->mobile : $request->input('mobile', '');
            $company = $authenticatedOtp ? $passenger->company_id : config('mobile.company_id');
            $digits = is_scalar($mobile) ? Controls::mobile(substr((string) $mobile, 0, 100)) : '';
            return hash('sha256', $company . ':' . $digits);
        };
        $response = function (Request $request, array $headers) {
            $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
                'data' => null,
                'errors' => (object) [],
            ], 429, $headers);
        };
        RateLimiter::for('mobile-login', function (Request $request) use ($accountKey, $response) {
            return [
                Limit::perMinute((int) Controls::policy((int) config('mobile.company_id'))['login_per_minute'])->by('account:' . $accountKey($request))->response($response),
                Limit::perMinute(20)->by('ip:' . $request->ip())->response($response),
            ];
        });
        RateLimiter::for('mobile-otp-send', function (Request $request) use ($accountKey, $response) {
            $limits = Controls::otpLimits($request);
            return [
                Limit::perMinutes(1440, max(0, $limits['day']))->by('account-day:' . $accountKey($request))->response($response),
                Limit::perMinutes(43200, max(0, $limits['month']))->by('account-month:' . $accountKey($request))->response($response),
                Limit::perMinute($limits['minute'])->by('account-minute:' . $accountKey($request))->response($response),
                Limit::perHour($limits['hour'])->by('account-hour:' . $accountKey($request))->response($response),
                Limit::perMinute(10)->by('ip:' . $request->ip())->response($response),
            ];
        });
        RateLimiter::for('mobile-otp-verify', function (Request $request) use ($accountKey, $response) {
            return [
                Limit::perMinutes(10, (int) Controls::policy((int) config('mobile.company_id'))['otp_verify_per_ten_minutes'])->by('account:' . $accountKey($request))->response($response),
                Limit::perMinute(30)->by('ip:' . $request->ip())->response($response),
            ];
        });
        RateLimiter::for('mobile-password-reset', function (Request $request) use ($accountKey, $response) {
            return [
                Limit::perMinute((int) Controls::policy((int) config('mobile.company_id'))['password_reset_per_minute'])->by('account:' . $accountKey($request))->response($response),
                Limit::perMinute(20)->by('ip:' . $request->ip())->response($response),
            ];
        });
    }
}
