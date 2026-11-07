<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Date lue dans l'URL (« ?date=2026-11-05 ») ; null si absente ou invalide.
 */
class DateParam
{
    public static function from(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
    }
}
