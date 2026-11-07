<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classe « HistoriqueEtat » (RG-23) : une ligne par changement d'état.
 */
#[Fillable(['user_id', 'old_status', 'new_status', 'changed_at'])]
class AppointmentStatusChange extends Model
{
    /** La date est dans changed_at ; pas de created_at/updated_at. */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_status' => AppointmentStatus::class,
            'new_status' => AppointmentStatus::class,
            'changed_at' => 'immutable_datetime',
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
     * Auteur du changement (null = système).
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
