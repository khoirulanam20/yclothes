<?php

namespace App\Services;

/**
 * Placeholder untuk integrasi DOKU payment gateway.
 */
class DokuService
{
    public static function isActive(): bool
    {
        return (bool) config('services.doku.enabled', false)
            && filled(config('services.doku.client_id'))
            && filled(config('services.doku.secret_key'));
    }
}
