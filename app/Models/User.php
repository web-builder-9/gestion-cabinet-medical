<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * « role » n'est volontairement PAS dans Fillable : un formulaire ne doit
 * jamais pouvoir changer le rôle d'un compte par affectation de masse.
 * On l'affecte explicitement : $user->role = Role::Secretary;
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valeurs par défaut (identiques à celles de la migration), pour que
     * $user->role soit renseigné même avant relecture en base.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'patient',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isDoctor(): bool
    {
        return $this->role === Role::Doctor;
    }

    public function isSecretary(): bool
    {
        return $this->role === Role::Secretary;
    }

    public function isPatient(): bool
    {
        return $this->role === Role::Patient;
    }

    /**
     * Personnel du cabinet : médecin ou secrétaire.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(Role::Doctor, Role::Secretary);
    }

    /**
     * Fiche patient liée à ce compte (uniquement pour le rôle patient).
     *
     * @return HasOne<Patient, $this>
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Rendez-vous dont cet utilisateur est le médecin.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointmentsAsDoctor(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeDoctors(Builder $query): void
    {
        $query->where('role', Role::Doctor->value);
    }
}
