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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['first_name', 'last_name', 'cin', 'birth_date', 'gender', 'phone', 'email', 'address'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    /**
     * « Prénom Nom », utilisable comme $patient->full_name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Le téléphone est toujours enregistré au format « 22 123 456 »,
     * quelle que soit la saisie (« 22123456 », « +216 22 12 34 56 »…).
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => self::formatPhone($value));
    }

    public static function formatPhone(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        // Indicatif tunisien facultatif : +216 ou 00216.
        if (strlen($digits) === 11 && str_starts_with($digits, '216')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '00216')) {
            $digits = substr($digits, 5);
        }

        if (strlen($digits) !== 8) {
            return $value; // format inattendu : on ne touche à rien (la validation l'aura refusé)
        }

        return substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 3);
    }

    /**
     * Âge en années, calculé à partir de la date de naissance.
     */
    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
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
     * Recherche par nom, prénom, CIN ou téléphone.
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
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('cin', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }
}
