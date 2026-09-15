<?php

return [
    'company_id' => env('MOBILE_COMPANY_ID'),
    'terminal_id' => env('MOBILE_TERMINAL_ID'),
    'booking_user_id' => env('MOBILE_BOOKING_USER_ID'),
    'maximum_selectable_seats' => (int) env('MOBILE_MAXIMUM_SELECTABLE_SEATS', 5),
    'latest_version' => env('MOBILE_LATEST_VERSION', '1.0.0'),
    'minimum_supported_version' => env('MOBILE_MINIMUM_SUPPORTED_VERSION', '1.0.0'),
    'force_update' => (bool) env('MOBILE_FORCE_UPDATE', false),
    'maintenance_mode' => (bool) env('MOBILE_MAINTENANCE_MODE', false),
    'maintenance_message' => env('MOBILE_MAINTENANCE_MESSAGE'),
    'android_store_url' => env('MOBILE_ANDROID_STORE_URL'),
    'ios_store_url' => env('MOBILE_IOS_STORE_URL'),
    'support_phone' => env('MOBILE_SUPPORT_PHONE'),
    'support_whatsapp' => env('MOBILE_SUPPORT_WHATSAPP'),
    'support_email' => env('MOBILE_SUPPORT_EMAIL'),
    'payment_methods' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MOBILE_PAYMENT_METHODS', ''))
    ))),
    'features' => [
        'wallet' => true,
        'online_payments' => false,
        'notifications' => false,
        'promotions' => false,
    ],
    'otp' => [
        'driver' => env('MOBILE_OTP_DRIVER', 'disabled'),
        'ttl_minutes' => (int) env('MOBILE_OTP_TTL_MINUTES', 10),
        'whatsapp' => [
            'api_key' => env('MOBILE_WHATSAPP_API_KEY'),
            'client_code' => env('MOBILE_WHATSAPP_CLIENT_CODE', 'kainat-travels'),
            'template' => env('MOBILE_WHATSAPP_OTP_TEMPLATE', 'otp'),
            'language' => env('MOBILE_WHATSAPP_LANGUAGE', 'en'),
        ],
    ],
];
