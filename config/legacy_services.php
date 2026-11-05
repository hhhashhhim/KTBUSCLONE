<?php

// Legacy ERP helpers keep their existing contracts; credentials are server-only.
return [
    'jazzcash' => [
        'merchant' => env('LEGACY_JAZZCASH_MERCHANT_ID', env('MOBILE_JAZZCASH_MERCHANT_ID')),
        'password' => env('LEGACY_JAZZCASH_PASSWORD', env('MOBILE_JAZZCASH_PASSWORD')),
        'salt' => env('LEGACY_JAZZCASH_INTEGRITY_SALT', env('MOBILE_JAZZCASH_INTEGRITY_SALT')),
        'redirectionUrl' => 'https://onlinepayments.jazzcash.com.pk/payment-orchestrator/CustomerPortal/transactionmanagement/merchantform',
        'walletUrl' => 'https://onlinepayments.jazzcash.com.pk/payment-orchestrator/api/v2/rest/payments/m-wallet',
        'statusUrl' => 'https://onlinepayments.jazzcash.com.pk/payment-orchestrator/api/v2/rest/payments/status/inquiry',
    ],
    'whatsapp_api_key' => env('LEGACY_WHATSAPP_API_KEY', env('MOBILE_WHATSAPP_API_KEY')),
    'report_whatsapp_auth_key' => env('LEGACY_REPORT_WHATSAPP_AUTH_KEY'),
    'report_whatsapp_session' => env('LEGACY_REPORT_WHATSAPP_SESSION'),
];
