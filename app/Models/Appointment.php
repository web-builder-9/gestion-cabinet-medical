<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Exceptions\InvalidStatusTransition;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * Classe « RendezVous ». Méthodes du diagramme : chevauche() → overlaps(),
 * changerEtat() → changeStatus().
 */
#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'ends_at', 'reason', 'status', 'notes'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * RG-23 : chaque état pris par un rendez-vous (création comprise) est historisé,
     * quel que soit le code qui le modifie. L'auteur est l'utilisateur connecté
     * (null pour une action automatique, comme une commande planifiée).
     */
    protected static function booted(): void
    {
        static::created(function (Appointment $appointment) {
            $appointment->recordStatusChange(null, $appointment->status);
        });

        static::updated(function (Appointment $appointment) {
            if ($appointment->wasChanged('status')) {
                $previous = $appointment->getOriginal('status');
                $appointment->recordStatusChange($previous, $appointment->status);
            }
        });
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        // withTrashed : un rendez-vous garde son patient même si sa fiche est archivée.
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return HasMany<AppointmentStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(AppointmentStatusChange::class)->orderBy('changed_at')->orderBy('id');
    }

    /**
     * @return HasOne<Consultation, $this>
     */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    /**
     * @return HasMany<AppointmentNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AppointmentNotification::class);
    }

    /**
     * RG-17 : change l'état en respectant les transitions autorisées.
     * L'historique (RG-23) est enregistré par l'événement « updated ».
     *
     * @throws InvalidStatusTransition
     */
    public function changeStatus(AppointmentStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new InvalidStatusTransition($this->status, $next);
        }

        $this->status = $next;
        $this->save();
    }

    public function isActive(): bool
    {
        return $this->status->blocksSlot();
    }

    /**
     * RG-16 : heure limite pour modifier ou annuler en ligne (24 h avant par défaut).
     */
    public function onlineChangeDeadline(): CarbonImmutable
    {
        return $this->starts_at->subHours(Setting::current()->cancellation_hours);
    }

    /**
     * RG-16 : le patient peut-il encore modifier ou annuler ce rendez-vous lui-même ?
     * Passé le délai, seul le secrétariat peut le faire.
     */
    public function canBeChangedOnline(): bool
    {
        return $this->isActive() && now()->lt($this->onlineChangeDeadline());
    }

    /**
     * Méthode « chevauche() » du diagramme : ce rendez-vous chevauche-t-il un
     * autre rendez-vous actif du même médecin (RG-12) ou du même patient (RG-13) ?
     */
    public function overlaps(): bool
    {
        return static::query()->active()->whereKeyNot($this->getKey())
            ->where(fn (Builder $q) => $q->where('doctor_id', $this->doctor_id)->orWhere('patient_id', $this->patient_id))
            ->where('starts_at', '<', $this->ends_at)
            ->where('ends_at', '>', $this->starts_at)
            ->exists();
    }

    /**
     * Ajoute une ligne d'historique.
     */
    public function recordStatusChange(?AppointmentStatus $old, AppointmentStatus $new): void
    {
        $this->statusChanges()->create([
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => $new,
            'changed_at' => now(),
        ]);
    }

    /**
     * Rendez-vous qui occupent un créneau : « En attente » ou « Confirmé » (RG-12).
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::activeValues());
    }

    /**
     * Rendez-vous actifs d'un médecin qui chevauchent [start, end[ (RG-12).
     *
     * Deux créneaux se chevauchent si chacun commence avant la fin de l'autre :
     *   existant.debut < nouveau.fin  ET  existant.fin > nouveau.debut
     * Des créneaux qui se touchent (10:00-10:30 puis 10:30-11:00) ne se chevauchent pas.
     * $ignoreId : lors d'un déplacement, le rendez-vous ne doit pas se gêner lui-même.
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
        $query->where('doctor_id', $doctorId)->active()->during($start, $end)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }

    /**
     * Rendez-vous actifs d'un patient qui chevauchent [start, end[ (RG-13).
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeOverlappingForPatient(
        Builder $query,
        int $patientId,
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $ignoreId = null,
    ): void {
        $query->where('patient_id', $patientId)->active()->during($start, $end)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }

    /**
     * Rendez-vous qui coupent l'intervalle [start, end[.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeDuring(Builder $query, DateTimeInterface $start, DateTimeInterface $end): void
    {
        $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /**
     * Rendez-vous qui tombent pendant une indisponibilité de leur médecin
     * (déclarée après la prise du rendez-vous) : le secrétariat doit les déplacer.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeDuringUnavailability(Builder $query): void
    {
        $query->whereExists(
            Unavailability::query()
                ->whereColumn('unavailabilities.doctor_id', 'appointments.doctor_id')
                ->whereColumn('unavailabilities.starts_at', '<', 'appointments.ends_at')
                ->whereColumn('unavailabilities.ends_at', '>', 'appointments.starts_at')
        );
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    public function scopeOnDate(Builder $query, DateTimeInterface $date): void
    {
        $query->whereDate('starts_at', $date->format('Y-m-d'));
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now());
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('starts_at', '<', now());
    }
}
