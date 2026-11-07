#!/bin/sh
# Service « cron » de Railway (« Custom Start Command ») :
#   chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh
# Lance le planificateur Laravel chaque minute ; il exécute lui-même
# rendez-vous:rappels toutes les heures (routes/console.php, BF-29).
while true; do
    echo "Planificateur : $(date)"
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done
