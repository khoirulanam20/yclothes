<?php

namespace App\Support;

class WilayahCode
{
    /**
     * Normalize wilayah codes to Kemendagri dotted format (e.g. 3373 → 33.73).
     */
    public static function normalize(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return $code;
        }

        $code = trim($code);

        if (str_contains($code, '.')) {
            return $code;
        }

        $digits = preg_replace('/\D/', '', $code) ?? '';

        if ($digits === '') {
            return $code;
        }

        // Province: 2 digits
        if (strlen($digits) === 2) {
            return $digits;
        }

        // Regency: 4 digits → PP.RR
        if (strlen($digits) === 4) {
            return substr($digits, 0, 2).'.'.substr($digits, 2, 2);
        }

        // District: 6 digits → PP.RR.DD
        if (strlen($digits) === 6) {
            return substr($digits, 0, 2).'.'.substr($digits, 2, 2).'.'.substr($digits, 4, 2);
        }

        // Village: 10 digits → PP.RR.DD.VVVV
        if (strlen($digits) === 10) {
            return substr($digits, 0, 2).'.'.substr($digits, 2, 2).'.'.substr($digits, 4, 2).'.'.substr($digits, 6, 4);
        }

        return $code;
    }

    /**
     * Compare two wilayah codes after normalization.
     */
    public static function equals(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return self::normalize($a) === self::normalize($b);
    }
}
