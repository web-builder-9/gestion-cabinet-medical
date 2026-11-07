<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Notifications\AppointmentNotice;

/**
 * « Service de notification » du cahier des charges (BF-28) : envoie
 * l'e-mail au patient et le note dans le journal appointment_notifications.
 * Un patient sans adresse e-mail (inscrit par le secrétariat) n'est pas
 * prévenu : la notification est notée « non envoyée ».
 */
class AppointmentNotifier
{
    public function send(Appointment $appointment, NotificationType $type): AppointmentNotification
    {
        $user = $appointment->patient?->user;
        $canSend = $user !== null && $user->email !== null && $user->is_active;

        if ($canSend) {
            $user->notify(new AppointmentNotice($appointment, $type));
        }

        return $appointment->notifications()->create([
            'type' => $type,
            'is_sent' => $canSend,
            'sent_at' => $canSend ? now() : null,
        ]);
    }
}
