<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'M';
    case Female = 'F';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Homme',
            self::Female => 'Femme',
        };
    }
}
