<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

class HtmlSanitizer
{
    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return Purifier::clean($html, 'default');
    }
}
