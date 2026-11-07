<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe « ParametresCabinet » : une seule ligne, réglée par l'administrateur (BF-26).
 * Lecture : Setting::current()->cancellation_hours
 */
#[Fillable([
    'cabinet_name', 'address', 'phone',
    'opening_time', 'closing_time', 'saturday_opening_time', 'saturday_closing_time',
    'default_slot_minutes', 'cancellation_hours', 'booking_days_ahead', 'reminder_hours',
])]
class Setting extends Model
{
    /**
     * Valeurs par défaut (identiques à la migration), utilisées tant que
     * l'administrateur n'a rien enregistré.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cabinet_name' => 'Cabinet médical',
        'opening_time' => '08:00',
        'closing_time' => '17:00',
        'saturday_opening_time' => null,
        'saturday_closing_time' => null,
        'default_slot_minutes' => 30,
        'cancellation_hours' => 24,
        'booking_days_ahead' => 60,
        'reminder_hours' => 24,
    ];

    protected function casts(): array
    {
        return [
            'default_slot_minutes' => 'integer',
            'cancellation_hours' => 'integer',
            'booking_days_ahead' => 'integer',
            'reminder_hours' => 'integer',
        ];
    }

    /**
     * Les paramètres du cabinet (la ligne enregistrée, ou les valeurs par défaut).
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static;
    }

    /**
     * Heure au format « 08:00 » (la base peut renvoyer « 08:00:00 »).
     */
    public function time(string $field): ?string
    {
        $value = $this->getAttribute($field);

        return $value === null ? null : substr((string) $value, 0, 5);
    }

    public function isOpenOnSaturday(): bool
    {
        return $this->saturday_opening_time !== null && $this->saturday_closing_time !== null;
    }

    /**
     * Ville du cabinet : dernière partie de l'adresse (« …, Midoun, Djerba » → « Djerba »).
     */
    public function city(): ?string
    {
        if (! $this->address) {
            return null;
        }

        return trim((string) last(explode(',', $this->address))) ?: null;
    }
}
