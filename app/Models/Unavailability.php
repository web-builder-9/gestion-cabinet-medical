<?php

namespace App\Models;

use Database\Factories\UnavailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
