#!/bin/sh
# Commande de pré-déploiement Railway (« Pre-Deploy Command ») :
#   chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh
# Elle s'exécute avant chaque mise en ligne d'une nouvelle version.
set -e

# Tables : nouvelles migrations seulement, jamais de remise à zéro en production.
php artisan migrate --force

# Données de démonstration au premier déploiement (variable SEED_DEMO=true).
if [ "$SEED_DEMO" = "true" ]; then
    php artisan demo:seed-if-empty
fi

# Caches de production (configuration, routes, vues, événements).
php artisan optimize:clear
php artisan optimize
