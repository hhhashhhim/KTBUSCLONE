<?php

use App\Models\MobileAppConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class EnableMobileWalletFeature extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mobile_app_configs')) {
            return;
        }

        MobileAppConfig::query()->each(function (MobileAppConfig $config) {
            $features = (array) $config->features;
            $features['wallet'] = true;
            $config->features = $features;
            $config->save();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('mobile_app_configs')) {
            return;
        }

        MobileAppConfig::query()->each(function (MobileAppConfig $config) {
            $features = (array) $config->features;
            unset($features['wallet']);
            $config->features = $features;
            $config->save();
        });
    }
}
