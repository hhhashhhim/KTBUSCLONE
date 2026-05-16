<?php

namespace App\Helpers;

use Carbon\Carbon;
use Throwable;

class BusColorHelper
{
    public const DATE_TIME_FORMAT = 'd-m-Y h:i A';

    public const COLOR_RED = 'red';
    public const COLOR_YELLOW = 'yellow';
    public const COLOR_GREEN = 'green';
    public const COLOR_WHITE = 'white';

    public static function getColor($departureDateTime, $actionDateTime): string
    {
        $departure = self::parse($departureDateTime);
        $action = self::parse($actionDateTime);

        if (!$departure || !$action) {
            return self::COLOR_WHITE;
        }

        if ($action->greaterThan($departure)) {
            return self::COLOR_RED;
        }

        $differenceInMinutes = $departure->diffInMinutes($action, false) * -1;

        if ($differenceInMinutes <= 30) {
            return self::COLOR_YELLOW;
        }

        if ($differenceInMinutes <= 120) {
            return self::COLOR_GREEN;
        }

        if ($differenceInMinutes <= 360) {
            return self::COLOR_WHITE;
        }

        return self::COLOR_WHITE;
    }

    public static function parse($value): ?Carbon
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat(self::DATE_TIME_FORMAT, $normalized);

            if (!$parsed || $parsed->format(self::DATE_TIME_FORMAT) !== $normalized) {
                return null;
            }

            return $parsed;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public static function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim((string) $value));

        if ($normalized === '') {
            return null;
        }

        $separatorPosition = strpos($normalized, ' - ');
        if ($separatorPosition !== false) {
            $normalized = trim(substr($normalized, 0, $separatorPosition));
        }

        return $normalized === '' ? null : $normalized;
    }
}
