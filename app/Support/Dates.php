<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Dates courtes des listes : « aujourd'hui 09:12 », « hier 18:40 », « mar. 3 nov. 15:00 ».
 */
class Dates
{
    public static function short(DateTimeInterface $date, bool $withTime = true): string
    {
        $date = Carbon::instance($date);

        $day = match (true) {
            $date->isToday() => 'aujourd\'hui',
            $date->isYesterday() => 'hier',
            $date->isTomorrow() => 'demain',
            default => $date->translatedFormat('D j M'),
        };

        return $withTime ? $day.' '.$date->format('H:i') : $day;
    }
}
