<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Notifications\AppointmentNotice;
use Illuminate\Database\Eloquent\Builder;

/**
 * « Service de notification » du cahier des charges (BF-28, BF-29) : envoie
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

    /**
     * BF-29 : rappel des rendez-vous confirmés qui commencent dans les N heures
     * (paramètre du cabinet), une seule fois par rendez-vous.
     *
     * @return int nombre de rappels traités
     */
    public function sendReminders(int $hours): int
    {
        $appointments = Appointment::query()
            ->with('patient.user')
            ->where('status', AppointmentStatus::Confirmed)
            ->whereBetween('starts_at', [now(), now()->addHours($hours)])
            ->whereDoesntHave('notifications', fn (Builder $q) => $q->where('type', NotificationType::Reminder))
            ->get();

        $appointments->each(fn (Appointment $appointment) => $this->send($appointment, NotificationType::Reminder));

        return $appointments->count();
    }
}
