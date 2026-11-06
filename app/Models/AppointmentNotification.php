<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classe « Notification » : journal des e-mails envoyés pour un rendez-vous.
 */
#[Fillable(['type', 'is_sent', 'sent_at'])]
class AppointmentNotification extends Model
{
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'is_sent' => 'boolean',
            'sent_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
