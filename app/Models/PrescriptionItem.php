<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'ordonnance : médicament, dosage, posologie, durée (maquette 14).
 */
#[Fillable(['position', 'medication', 'dosage', 'posology', 'duration'])]
class PrescriptionItem extends Model
{
    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * « 2 bouffées si gêne, pendant 10 jours. » (ordonnance et compte rendu).
     */
    public function instructions(): string
    {
        $text = collect([$this->posology, $this->duration ? "pendant {$this->duration}" : null])->filter()->implode(', ');

        return $text === '' ? '' : ucfirst($text).'.';
    }
}
