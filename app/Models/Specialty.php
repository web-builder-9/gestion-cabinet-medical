<?php

namespace App\Models;

use Database\Factories\SpecialtyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Classe « Specialite » : regroupe plusieurs médecins (RG-06).
 */
#[Fillable(['name', 'description'])]
class Specialty extends Model
{
    /** @use HasFactory<SpecialtyFactory> */
    use HasFactory;

    /**
     * @return HasMany<Doctor, $this>
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * RG-07 : une spécialité attribuée à au moins un médecin ne peut pas être supprimée.
     */
    public function isDeletable(): bool
    {
        return ! $this->doctors()->exists();
    }
}
