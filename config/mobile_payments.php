<?php

return [
    'environment' => env('MOBILE_PAYMENT_ENVIRONMENT', 'sandbox'),
    // Enable only after merchant configuration, migrations and sandbox acceptance.
    'enabled' => (bool) env('MOBILE_ONLINE_PAYMENTS_ENABLED', false),
    // Preview exposes configured methods, but prevents bookings and all gateway requests.
    'preview_only' => (bool) env('MOBILE_PAYMENT_PREVIEW_ONLY', false),
    'checkout_minutes' => (int) env('MOBILE_CHECKOUT_MINUTES', 10),
    'jazzcash' => [
        'merchant_id' => env('MOBILE_JAZZCASH_MERCHANT_ID'),
        'password' => env('MOBILE_JAZZCASH_PASSWORD'),
        'integrity_salt' => env('MOBILE_JAZZCASH_INTEGRITY_SALT'),
        // Match the working website unless the merchant contract specifies routing values.
        'bank_id' => env('MOBILE_JAZZCASH_BANK_ID', ''),
        'product_id' => env('MOBILE_JAZZCASH_PRODUCT_ID', ''),
        'checkout_url' => env('MOBILE_JAZZCASH_CHECKOUT_URL'),
        'status_url' => env('MOBILE_JAZZCASH_STATUS_URL'),
    ],
    'bank_alfalah' => [
        'merchant_id' => env('MOBILE_ALFALAH_MERCHANT_ID'),
        'store_id' => env('MOBILE_ALFALAH_STORE_ID'),
        'merchant_hash' => env('MOBILE_ALFALAH_MERCHANT_HASH'),
        'username' => env('MOBILE_ALFALAH_USERNAME'),
        'password' => env('MOBILE_ALFALAH_PASSWORD'),
        'key1' => env('MOBILE_ALFALAH_KEY1'),
        'key2' => env('MOBILE_ALFALAH_KEY2'),
        'base_url' => env('MOBILE_ALFALAH_BASE_URL', 'https://sandbox.bankalfalah.com'),
    ],
];
