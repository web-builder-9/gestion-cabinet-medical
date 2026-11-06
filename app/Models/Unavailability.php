<?php

namespace App\Models;

use Database\Factories\UnavailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classe « Indisponibilite » : absence ponctuelle d'un médecin (BF-18).
 */
#[Fillable(['doctor_id', 'starts_at', 'ends_at', 'reason'])]
class Unavailability extends Model
{
    /** @use HasFactory<UnavailabilityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
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
     * Indisponibilités en cours ou à venir.
     *
     * @param  Builder<Unavailability>  $query
     */
    public function scopeCurrentOrUpcoming(Builder $query): void
    {
        $query->where('ends_at', '>', now());
    }

    /**
     * « Ven. 6 nov. · 14:00 – 17:00 » sur une journée,
     * « Du 21 déc. 08:00 au 26 déc. 12:00 » sur plusieurs jours (maquette 12).
     */
    public function periodLabel(): string
    {
        if ($this->starts_at->isSameDay($this->ends_at)) {
            return ucfirst($this->starts_at->translatedFormat('D j M'))
                .' · '.$this->starts_at->format('H:i').' – '.$this->ends_at->format('H:i');
        }

        return 'Du '.$this->starts_at->translatedFormat('j M H:i').' au '.$this->ends_at->translatedFormat('j M H:i');
    }

    /**
     * Rendez-vous actifs du médecin pendant cette indisponibilité : le
     * secrétariat doit les déplacer ou les annuler.
     *
     * @return Builder<Appointment>
     */
    public function conflictingAppointments(): Builder
    {
        return Appointment::query()
            ->where('doctor_id', $this->doctor_id)
            ->active()
            ->during($this->starts_at, $this->ends_at);
    }
}
