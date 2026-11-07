<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Classe « Utilisateur » : attributs communs à tous les rôles.
 * Patient et Médecin la spécialisent (modèles Patient et Doctor, liés en 1-1).
 *
 * « role » et « is_active » ne sont volontairement PAS dans Fillable : un
 * formulaire ne doit jamais pouvoir les changer par affectation de masse.
 */
#[Fillable(['last_name', 'first_name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valeurs par défaut (identiques à la migration), disponibles avant relecture en base.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'patient',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * « Prénom Nom ».
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Initiales affichées dans les pastilles (« AB » pour Amira Ben Salah).
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(
            mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1)
        ));
    }

    /**
     * Téléphone toujours enregistré au format « 22 123 456 ».
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => PhoneNumber::format($value));
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isPatient(): bool
    {
        return $this->role === Role::Patient;
    }

    public function isDoctor(): bool
    {
        return $this->role === Role::Doctor;
    }

    public function isSecretary(): bool
    {
        return $this->role === Role::Secretary;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Personnel soignant et administratif qui gère les patients : médecin ou secrétaire.
     */
    public function isCareStaff(): bool
    {
        return $this->hasRole(Role::Doctor, Role::Secretary);
    }

    /**
     * Profil patient (spécialisation de l'utilisateur).
     *
     * @return HasOne<Patient, $this>
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Profil médecin (spécialisation de l'utilisateur).
     *
     * @return HasOne<Doctor, $this>
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeWithRole(Builder $query, Role ...$roles): void
    {
        $query->whereIn('role', array_map(fn (Role $role) => $role->value, $roles));
    }

    /**
     * Comptes du personnel (médecins, secrétaires, administrateurs).
     *
     * @param  Builder<User>  $query
     */
    public function scopeStaff(Builder $query): void
    {
        $query->withRole(...Role::staff());
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
