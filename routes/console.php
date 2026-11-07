<?php

use Illuminate\Support\Facades\Schedule;

// Rappels de rendez-vous par e-mail (BF-29), vérifiés toutes les heures.
// En production, une seule tâche cron lance le planificateur chaque minute :
//   * * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
// En local : php artisan schedule:work (ou directement php artisan rendez-vous:rappels).
Schedule::command('rendez-vous:rappels')->hourly()->withoutOverlapping();
