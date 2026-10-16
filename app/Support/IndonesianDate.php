<?php

namespace App\Support;

use Carbon\CarbonInterface;

class IndonesianDate
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public static function date(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = $date->copy()->timezone('Asia/Jakarta');

        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    public static function dateTime(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return self::date($date).', '.$date->copy()->timezone('Asia/Jakarta')->format('H.i');
    }
}
