<?php

namespace App\Models;

use Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Classe « Consultation » : compte rendu d'un rendez-vous terminé (RG-19).
 * Le patient et le médecin se déduisent du rendez-vous.
 */
#[Fillable(['appointment_id', 'consulted_at', 'reason', 'observations', 'diagnosis', 'treatment'])]
class Consultation extends Model
{
    /** @use HasFactory<ConsultationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'consulted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Lignes de l'ordonnance, dans l'ordre de saisie.
     *
     * @return HasMany<PrescriptionItem, $this>
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('position');
    }

    /**
     * Numéro affiché sur l'ordonnance (« ORD-2026-0042 »).
     */
    public function prescriptionNumber(): string
    {
        return sprintf('ORD-%s-%04d', $this->consulted_at->format('Y'), $this->id);
    }
}
