<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\AppointmentNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Rappel par e-mail avant le rendez-vous (BF-29). Lancée toutes les heures par
 * le planificateur (routes/console.php) ; en local : php artisan rendez-vous:rappels
 */
#[Signature('rendez-vous:rappels')]
#[Description('Envoie le rappel par e-mail des rendez-vous confirmés des prochaines heures')]
class SendAppointmentReminders extends Command
{
    public function handle(AppointmentNotifier $notifier): int
    {
        $hours = Setting::current()->reminder_hours;
        $count = $notifier->sendReminders($hours);

        $this->info("{$count} rappel(s) traité(s) pour les rendez-vous des {$hours} prochaines heures.");

        return self::SUCCESS;
    }
}
