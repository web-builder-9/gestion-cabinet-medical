# Déploiement sur Railway

Railway héberge l'application avec une URL publique en HTTPS (BNF-05, BNF-14). Il reconnaît Laravel tout seul
(PHP-FPM et Caddy) : pas de Dockerfile à écrire. Le projet utilise trois services :

| Service   | Rôle                                                                        |
| --------- | --------------------------------------------------------------------------- |
| `MySQL`   | Base de données (sauvegardes dans l'onglet *Backups* du service)            |
| `app`     | L'application web, seul service avec une adresse publique                   |
| `cron`    | Lance le planificateur Laravel chaque minute (rappels par e-mail, BF-29)    |

Les scripts utilisés sont dans le dossier [`railway/`](../railway).

## 1. Créer le projet

1. Se connecter sur [railway.com](https://railway.com) avec le compte GitHub qui a accès au dépôt.
2. *New Project* → *Deploy from GitHub repo* → `web-builder-9/gestion-cabinet-medical`. Ce premier service est `app`.
3. Dans le projet : *Create* → *Database* → *MySQL*.

## 2. Configurer le service `app`

Onglet **Variables** (*Raw Editor*), en remplaçant la clé par celle donnée par `php artisan key:generate --show`
(à lancer sur votre machine) :

```dotenv
APP_NAME="Cabinet médical"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=fr
APP_TIMEZONE=Africa/Tunis

DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
CACHE_STORE=database

LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter

MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@cabinet.test
MAIL_FROM_NAME="Cabinet médical"

# Données de démonstration au premier déploiement (base vide), pour la présentation.
SEED_DEMO=true
```

Onglet **Settings** :

- *Build* → *Custom Build Command* : `npm run build`
- *Deploy* → *Pre-Deploy Command* : `chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh`
  (migrations, données de démonstration si la base est vide, caches de production)
- *Networking* → *Generate Domain* : l'adresse publique en `https://….up.railway.app`

Puis *Deploy*. À la fin, l'application répond sur l'adresse publique et `/up` renvoie une page « Application up ».

## 3. Créer le service `cron`

1. *Create* → *GitHub Repo* → le même dépôt ; renommer le service en `cron`.
2. **Variables** : les mêmes que `app` (bouton *Shared Variables* ou copier le *Raw Editor*), sans `SEED_DEMO`.
3. **Settings** → *Deploy* → *Custom Start Command* : `chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh`
4. Pas de domaine public pour ce service.

Le planificateur lance `rendez-vous:rappels` toutes les heures (`routes/console.php`). Les journaux du service `cron`
affichent « Planificateur : … » chaque minute.

## 4. Vérifier

- Se connecter avec les comptes de démonstration du README (mot de passe `password`).
- Prendre un rendez-vous en tant que patient, le confirmer en tant que secrétaire : l'e-mail apparaît dans les journaux
  (*Deployments* → *View logs*) puisque `MAIL_MAILER=log`.
- Télécharger une ordonnance PDF en tant que médecin.

## Envoyer de vrais e-mails (facultatif)

Remplacer les variables `MAIL_*` par celles d'un service SMTP (Brevo, Mailtrap, Gmail avec mot de passe
d'application…) :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=adresse-validee@exemple.tn
```

## Mises à jour

Chaque fusion dans `main` redéploie automatiquement `app` et `cron`. Les nouvelles migrations s'appliquent pendant le
pré-déploiement ; les données existantes sont conservées (jamais de `migrate:fresh` en production).
Pour repartir d'une base de démonstration neuve : supprimer puis recréer le service MySQL, et redéployer `app`.
