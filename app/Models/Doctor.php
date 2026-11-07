<?php

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Classe « Medecin » : spécialise Utilisateur (nom, e-mail… sont dans users).
 */
#[Fillable(['specialty_id', 'presentation'])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Specialty, $this>
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * @return HasMany<Availability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * @return HasMany<Unavailability, $this>
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * « Dr Prénom Nom ».
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => 'Dr '.$this->user->full_name);
    }

    /**
     * Médecins dont le compte est actif (seuls visibles et réservables).
     *
     * @param  Builder<Doctor>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereHas('user', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * Recherche par nom du médecin ou par spécialité (BF-02).
     *
     * @param  Builder<Doctor>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->whereHas('user', fn (Builder $u) => $u
                ->where('last_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%"))
            ->orWhereHas('specialty', fn (Builder $sp) => $sp->where('name', 'like', "%{$term}%")));
    }

    /**
     * Médecins en activité, avec leur compte et leur spécialité, triés par nom
     * (listes de choix, plannings).
     *
     * @return Collection<int, Doctor>
     */
    public static function activeByName(): Collection
    {
        return static::active()->with(['user', 'specialty'])->get()
            ->sortBy(fn (Doctor $doctor) => $doctor->user->last_name)
            ->values();
    }
}
