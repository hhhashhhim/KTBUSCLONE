<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Models\MobileAppConfig;
use App\Services\Mobile\MobileBookingService;
use Illuminate\Support\Facades\Schema;

class MobileAppController extends Controller
{
    use RespondsWithMobileApi;

    public function show(MobileBookingService $bookings)
    {
        $row = null;
        $companyId = (int) config('mobile.company_id');
        if ($companyId > 0 && Schema::hasTable('mobile_app_configs')) {
            $row = MobileAppConfig::where('company_id', $companyId)->first();
        }

        $features = array_merge((array) config('mobile.features', []), (array) optional($row)->features);
        $paymentMethods = $companyId > 0 ? $bookings->paymentMethods() : [];
        // Do not advertise modules that do not have a compatible mobile backend yet.
        $features['notifications'] = false;
        $features['promotions'] = false;
        $features['online_payments'] = !config('mobile_payments.preview_only') && count(array_diff($paymentMethods, ['counter'])) > 0;

        return $this->success([
            'latest_version' => optional($row)->latest_version ?: config('mobile.latest_version'),
            'minimum_supported_version' => optional($row)->minimum_supported_version ?: config('mobile.minimum_supported_version'),
            'force_update' => $row ? (bool) $row->force_update : (bool) config('mobile.force_update'),
            'maintenance_mode' => $row ? (bool) $row->maintenance_mode : (bool) config('mobile.maintenance_mode'),
            'maintenance_message' => optional($row)->maintenance_message ?: config('mobile.maintenance_message'),
            'android_store_url' => optional($row)->android_store_url ?: config('mobile.android_store_url'),
            'ios_store_url' => optional($row)->ios_store_url ?: config('mobile.ios_store_url'),
            'support_phone' => optional($row)->support_phone ?: config('mobile.support_phone'),
            'support_whatsapp' => optional($row)->support_whatsapp ?: config('mobile.support_whatsapp'),
            'support_email' => optional($row)->support_email ?: config('mobile.support_email'),
            'payment_environment' => config('mobile_payments.environment'),
            'payment_preview' => (bool) config('mobile_payments.preview_only'),
            'features' => $features,
        ], 'App configuration retrieved.');
    }
}
