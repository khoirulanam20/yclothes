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

    public static function normalizeStorageUrls(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $html = preg_replace(
            '#(?:\.\./)+storage/([^"\'>\s]+)#i',
            '/storage/$1',
            $html
        );

        return preg_replace(
            '#(?<=["\'])storage/([^"\'>\s]+)#i',
            '/storage/$1',
            $html
        );
    }

    public static function prepareRichText(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return self::normalizeStorageUrls(self::clean($html));
    }
}
