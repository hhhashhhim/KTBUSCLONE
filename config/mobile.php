<?php

return [
    'registration_ttl_hours' => (int) env('MOBILE_REGISTRATION_TTL_HOURS', 24),
    'biometric_login_enabled' => (bool) env('MOBILE_BIOMETRIC_LOGIN_ENABLED', true),
    'biometric_session_days' => (int) env('MOBILE_BIOMETRIC_SESSION_DAYS', 30),
    'company_id' => env('MOBILE_COMPANY_ID'),
    'terminal_id' => env('MOBILE_TERMINAL_ID'),
    'booking_user_id' => env('MOBILE_BOOKING_USER_ID'),
    'maximum_selectable_seats' => (int) env('MOBILE_MAXIMUM_SELECTABLE_SEATS', 5),
    'daily_seat_limit' => max(1, (int) env('MOBILE_DAILY_SEAT_LIMIT', 20)),
    'monthly_seat_limit' => max(1, (int) env('MOBILE_MONTHLY_SEAT_LIMIT', 100)),
    'maximum_pending_reservations' => max(1, (int) env('MOBILE_MAXIMUM_PENDING_RESERVATIONS', 2)),
    // A separate connection keeps mobile workers independent of existing ERP jobs.
    'background_queue_connection' => env('MOBILE_BACKGROUND_QUEUE_CONNECTION'),
    'background_queue_name' => env('MOBILE_BACKGROUND_QUEUE_NAME', 'mobile'),
    'notification_batch_size' => max(1, min(5000, (int) env('MOBILE_NOTIFICATION_BATCH_SIZE', 1000))),
    // Proposed retention policy: preview first, then explicitly enable on the mobile API host.
    'retention' => [
        'enabled' => (bool) env('MOBILE_RETENTION_ENABLED', false),
        'notifications_days' => env('MOBILE_RETENTION_NOTIFICATIONS_DAYS', 90),
        'login_events_days' => env('MOBILE_RETENTION_LOGIN_EVENTS_DAYS', 90),
        'push_devices_days' => env('MOBILE_RETENTION_PUSH_DEVICES_DAYS', 90),
        'deletion_audit_days' => env('MOBILE_RETENTION_DELETION_AUDIT_DAYS', 365),
        // Unconfirmed until all host, replica, export and backup copies are inventoried.
        'backup_max_days' => env('MOBILE_RETENTION_BACKUP_MAX_DAYS'),
        'batch_size' => env('MOBILE_RETENTION_BATCH_SIZE', 500),
    ],
    'latest_version' => env('MOBILE_LATEST_VERSION', '1.0.0'),
    'minimum_supported_version' => env('MOBILE_MINIMUM_SUPPORTED_VERSION', '1.0.0'),
    'force_update' => (bool) env('MOBILE_FORCE_UPDATE', false),
    'maintenance_mode' => (bool) env('MOBILE_MAINTENANCE_MODE', false),
    'maintenance_message' => env('MOBILE_MAINTENANCE_MESSAGE'),
    'android_store_url' => env('MOBILE_ANDROID_STORE_URL'),
    'ios_store_url' => env('MOBILE_IOS_STORE_URL'),
    'support_phone' => env('MOBILE_SUPPORT_PHONE') ?: '+923111777333',
    'support_whatsapp' => env('MOBILE_SUPPORT_WHATSAPP'),
    'support_email' => env('MOBILE_SUPPORT_EMAIL') ?: 'info@kainattravels.com',
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
