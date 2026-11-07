<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Carbon\CarbonImmutable;
use Database\Factories\AvailabilityFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classe « Disponibilite » : plage hebdomadaire d'un médecin (RG-09),
 * ex. : lundi 08:00-12:00, créneaux de 30 min.
 */
#[Fillable(['doctor_id', 'day_of_week', 'start_time', 'end_time', 'slot_minutes'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'slot_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Heure de début au format « 08:00 » (la base peut renvoyer « 08:00:00 »).
     */
    public function startLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endLabel(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }

    /**
     * « Lundi 08:00 – 12:00 » (messages d'erreur).
     */
    public function label(): string
    {
        return "{$this->day_of_week->label()} {$this->startLabel()} – {$this->endLabel()}";
    }

    /**
     * RG-10 : chevauchement avec la plage [start, end[ du même jour.
     * Heures au format « 08:00 » : l'ordre alphabétique est l'ordre chronologique.
     * Des plages qui se touchent (08:00-12:00 puis 12:00-14:00) ne se chevauchent pas.
     */
    public function overlaps(string $start, string $end): bool
    {
        return $this->startLabel() < $end && $this->endLabel() > $start;
    }

    /**
     * Début de la plage pour une date donnée.
     */
    public function startsOn(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->format('Y-m-d').' '.$this->startLabel());
    }

    public function endsOn(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->format('Y-m-d').' '.$this->endLabel());
    }
}
