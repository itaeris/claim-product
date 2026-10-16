<?php

namespace App\Support;

class Terbilang
{
    private const WORDS = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
    ];

    public static function rupiah(int $amount): string
    {
        return ($amount === 0 ? 'Nol' : self::words($amount)).' Rupiah';
    }

    private static function words(int $number): string
    {
        return trim(match (true) {
            $number < 12 => self::WORDS[$number],
            $number < 20 => self::words($number - 10).' Belas',
            $number < 100 => self::words(intdiv($number, 10)).' Puluh '.self::words($number % 10),
            $number < 200 => 'Seratus '.self::words($number - 100),
            $number < 1000 => self::words(intdiv($number, 100)).' Ratus '.self::words($number % 100),
            $number < 2000 => 'Seribu '.self::words($number - 1000),
            $number < 1_000_000 => self::words(intdiv($number, 1000)).' Ribu '.self::words($number % 1000),
            $number < 1_000_000_000 => self::words(intdiv($number, 1_000_000)).' Juta '.self::words($number % 1_000_000),
            default => self::words(intdiv($number, 1_000_000_000)).' Miliar '.self::words($number % 1_000_000_000),
        });
    }
}
