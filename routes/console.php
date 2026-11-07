<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Rappels de rendez-vous par e-mail (BF-29), vérifiés toutes les heures.
// En production, une seule tâche cron lance le planificateur chaque minute :
//   * * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
// En local : php artisan schedule:work (ou directement php artisan rendez-vous:rappels).
Schedule::command('rendez-vous:rappels')->hourly()->withoutOverlapping();

// Données de démonstration au premier déploiement seulement (base vide), pour la
// présentation. Lancée par railway/init-app.sh quand SEED_DEMO=true.
Artisan::command('demo:seed-if-empty', function () {
    if (User::exists()) {
        $this->info('La base contient déjà des comptes : rien à faire.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Charge les données de démonstration si la base est vide');
