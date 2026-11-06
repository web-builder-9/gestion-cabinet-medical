<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail « Mot de passe oublié » en français (BF-06).
 */
class ResetPasswordNotification extends Notification
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
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Vous avez demandé à changer le mot de passe de votre compte.')
            ->action('Choisir un nouveau mot de passe', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Ce lien est valable 24 heures. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.');
    }
}
