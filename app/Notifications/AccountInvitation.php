<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation envoyée quand le cabinet crée un compte (administrateur pour le
 * personnel, secrétaire pour un patient) : la personne choisit elle-même son
 * mot de passe, que personne d'autre ne connaît (maquettes 9 et 16).
 */
class AccountInvitation extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $cabinet = Setting::current()->cabinet_name;

        return (new MailMessage)
            ->subject("Votre compte — {$cabinet}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Un compte {$notifiable->role->label()} vient d'être créé pour vous sur l'application du {$cabinet}.")
            ->line('Pour l\'activer, choisissez votre mot de passe :')
            ->action('Choisir mon mot de passe', $this->url($notifiable))
            ->line('Ce lien est valable 24 heures. Passé ce délai, utilisez « Mot de passe oublié » sur la page de connexion.');
    }

    public function url(User $notifiable): string
    {
        return route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
    }
}
