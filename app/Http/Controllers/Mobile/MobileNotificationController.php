<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;

class MobileNotificationController extends Controller
{
    use RespondsWithMobileApi;

    public function index()
    {
        // No compatible passenger notification store exists in the current system.
        return $this->success([], 'Notifications are not enabled.');
    }
}
