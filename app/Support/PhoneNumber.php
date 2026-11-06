<?php

namespace App\Support;

/**
 * Numéros de téléphone tunisiens, toujours enregistrés au format « 22 123 456 ».
 * Un format unique rend la recherche et la règle d'unicité (RG-05) fiables.
 */
class PhoneNumber
{
    /** Format attendu après normalisation (utilisé par la validation). */
    public const PATTERN = '/^\d{2} \d{3} \d{3}$/';

    /**
     * Accepte « 22123456 », « 22 12 34 56 », « 22-123-456 », « +216 22 123 456 »…
     * Une saisie qui ne donne pas 8 chiffres est rendue telle quelle (la validation la refusera).
     */
    public static function format(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) === 11 && str_starts_with($digits, '216')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '00216')) {
            $digits = substr($digits, 5);
        }

        if (strlen($digits) !== 8) {
            return $value;
        }

        return substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 3);
    }
}
