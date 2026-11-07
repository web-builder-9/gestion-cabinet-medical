<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe « Patient » : spécialise Utilisateur. L'identité commune (nom, prénom,
 * e-mail, téléphone) est dans users ; ici, les attributs propres au patient.
 */
#[Fillable(['cin', 'birth_date', 'gender', 'address', 'data_consent', 'consent_given_at'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'data_consent' => 'boolean',
            'consent_given_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<MedicalRecord, $this>
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Consultations du patient, à travers ses rendez-vous.
     *
     * @return HasManyThrough<Consultation, Appointment, $this>
     */
    public function consultations(): HasManyThrough
    {
        return $this->hasManyThrough(Consultation::class, Appointment::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => $this->user->full_name);
    }

    /**
     * Âge en années, calculé à partir de la date de naissance.
     */
    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
    }

    /**
     * Le patient peut-il se connecter à son espace (compte actif avec e-mail) ?
     */
    public function hasPortalAccess(): bool
    {
        return $this->user->email !== null && $this->user->is_active;
    }

    /**
     * Recherche par nom, prénom, téléphone (dans users) ou CIN.
     * Exemple : Patient::search('Trabelsi')->get();
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('cin', 'like', $like)
                ->orWhereHas('user', function (Builder $user) use ($like) {
                    $user->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
        });
    }

    /**
     * Tri alphabétique par nom puis prénom (colonnes de users).
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeOrderByName(Builder $query): void
    {
        $query->orderBy(
            User::select('last_name')->whereColumn('users.id', 'patients.user_id')
        )->orderBy(
            User::select('first_name')->whereColumn('users.id', 'patients.user_id')
        );
    }
}
