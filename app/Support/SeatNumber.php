<?php

namespace App\Support;

class SeatNumber
{
    public static function normalize($seatNumber): string
    {
        return strtoupper(trim((string) $seatNumber));
    }

    public static function canonicalMap(array $seatMap): array
    {
        $canonical = [];

        foreach ($seatMap as $row) {
            foreach ((array) $row as $seat) {
                if (!is_array($seat) || !isset($seat['seatNo'])) {
                    continue;
                }

                $normalized = self::normalize($seat['seatNo']);
                if ($normalized !== '' && !isset($canonical[$normalized])) {
                    $canonical[$normalized] = (string) $seat['seatNo'];
                }
            }
        }

        return $canonical;
    }

    public static function canonicalizeMany(array $seatNumbers, array $seatMap): ?array
    {
        $canonical = self::canonicalMap($seatMap);
        $resolved = [];
        $seen = [];

        foreach ($seatNumbers as $seatNumber) {
            $normalized = self::normalize($seatNumber);
            if (!isset($canonical[$normalized]) || isset($seen[$normalized])) {
                return null;
            }

            $seen[$normalized] = true;
            $resolved[] = $canonical[$normalized];
        }

        return $resolved;
    }
}
