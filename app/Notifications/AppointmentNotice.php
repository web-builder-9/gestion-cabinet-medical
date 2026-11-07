<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail envoyé au patient pour un rendez-vous (BF-28, BF-29) : prise ou
 * confirmation, modification, annulation et rappel.
 */
class AppointmentNotice extends Notification
{
    public function __construct(
        public readonly Appointment $appointment,
        public readonly NotificationType $type,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $settings = Setting::current();
        $appointment = $this->appointment->loadMissing('doctor.user');
        $when = ucfirst($appointment->starts_at->translatedFormat('l j F Y')).' à '.$appointment->starts_at->format('H:i');
        $pending = $appointment->status === AppointmentStatus::Pending;

        $mail = (new MailMessage)->greeting("Bonjour {$notifiable->first_name},");

        $mail = match ($this->type) {
            NotificationType::Confirmation => $pending
                ? $mail->subject('Demande de rendez-vous reçue')
                    ->line('Nous avons bien reçu votre demande de rendez-vous :')
                    ->line("**{$when}** avec {$appointment->doctor->display_name}.")
                    ->line('Elle est en attente de confirmation par le secrétariat : vous recevrez un e-mail dès qu\'elle sera confirmée.')
                : $mail->subject('Rendez-vous confirmé')
                    ->line('Votre rendez-vous est confirmé :')
                    ->line("**{$when}** avec {$appointment->doctor->display_name}.")
                    ->line("Vous pouvez le modifier ou l'annuler en ligne jusqu'à {$settings->cancellation_hours} h avant."),
            NotificationType::Modification => $mail->subject('Rendez-vous modifié')
                ->line('Votre rendez-vous a été déplacé. Nouveau créneau :')
                ->line("**{$when}** avec {$appointment->doctor->display_name}.")
                ->line($pending ? 'Il est en attente de confirmation par le secrétariat.' : 'Il reste confirmé.'),
            NotificationType::Cancellation => $mail->subject('Rendez-vous annulé')
                ->line("Votre rendez-vous du **{$when}** avec {$appointment->doctor->display_name} est annulé.")
                ->line('Vous pouvez prendre un nouveau rendez-vous en ligne à tout moment.'),
            NotificationType::Reminder => $mail->subject('Rappel : rendez-vous le '.$appointment->starts_at->translatedFormat('j F').' à '.$appointment->starts_at->format('H:i'))
                ->line('Nous vous rappelons votre rendez-vous :')
                ->line("**{$when}** avec {$appointment->doctor->display_name}.")
                ->when($settings->address, fn (MailMessage $m) => $m->line("Adresse : {$settings->address}."))
                ->line('En cas d\'empêchement, prévenez le cabinet'.($settings->phone ? " au {$settings->phone}" : '').'.'),
        };

        return $mail->action('Voir mes rendez-vous', route('dashboard'))
            ->salutation("Cordialement,\n{$settings->cabinet_name}");
    }
}
