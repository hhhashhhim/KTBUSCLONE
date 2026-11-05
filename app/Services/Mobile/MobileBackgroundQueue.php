<?php

namespace App\Services\Mobile;

use RuntimeException;

class MobileBackgroundQueue
{
    public static function enabled(): bool
    {
        $connection = config('mobile.background_queue_connection');
        if (!$connection) { return false; }
        $driver = config('queue.connections.' . $connection . '.driver');
        if (!in_array($driver, ['redis', 'database', 'sqs', 'beanstalkd'], true)) {
            throw new RuntimeException('Mobile background work requires a durable queue connection.');
        }
        return true;
    }
}
