<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'ends_at', 'reason', 'status', 'notes'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Rendez-vous du même médecin qui chevauchent l'intervalle [start, end[.
     *
     * Deux créneaux se chevauchent si chacun commence avant la fin de l'autre :
     *   existant.debut < nouveau.fin  ET  existant.fin > nouveau.debut
     * Des créneaux qui se touchent (10:00-10:30 puis 10:30-11:00) ne sont donc
     * pas en conflit. Les rendez-vous annulés sont ignorés.
     *
     * $ignoreId sert lors d'une modification : le rendez-vous ne doit pas
     * entrer en conflit avec lui-même.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeOverlapping(
        Builder $query,
        int $doctorId,
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $ignoreId = null,
    ): void {
        $query->where('doctor_id', $doctorId)
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }

    /**
     * Indique si ce rendez-vous chevauche un autre rendez-vous du même médecin.
     */
    public function hasConflict(): bool
    {
        return static::query()
            ->overlapping($this->doctor_id, $this->starts_at, $this->ends_at, $this->getKey())
            ->exists();
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    public function scopeOnDate(Builder $query, DateTimeInterface $date): void
    {
        $query->whereDate('starts_at', $date->format('Y-m-d'));
    }
}
