<?php
return [
    'enabled' => (bool) env('MOBILE_NOTIFICATIONS_ENABLED', true),
    'push_enabled' => (bool) env('MOBILE_PUSH_ENABLED', false),
    // Private service-account JSON stays outside the web root and version control.
    'credentials_file' => env('MOBILE_FCM_CREDENTIALS_FILE'),
    'project_id' => env('MOBILE_FCM_PROJECT_ID'),
    'reminder_minutes' => 120,
];
