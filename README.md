# Gestion d'un cabinet médical

Projet d'intégration de 3e année de Licence (ISET Djerba) : application web de gestion d'un cabinet médical
(patients, rendez-vous, consultations, ordonnances, facturation, tableau de bord).

Stack : Laravel 13, MySQL, Blade, Tailwind CSS 4, Alpine.js, Laravel Breeze.

## Prérequis

- PHP 8.3 ou plus, avec les extensions `pdo_mysql`, `pdo_sqlite` (pour les tests), `mbstring`, `intl`, `zip`
- Composer
- Node.js 22 (LTS) et npm
- MySQL 8 ou MariaDB (XAMPP, Laragon, WAMP… conviennent)

Vérifier : `php -v`, `composer -V`, `node -v`.

## Installation

```bash
git clone https://github.com/web-builder-9/gestion-cabinet-medical.git
cd gestion-cabinet-medical

composer install
npm install

cp .env.example .env        # sous Windows : copy .env.example .env
php artisan key:generate
```

Créer la base de données (phpMyAdmin ou en SQL) :

```sql
CREATE DATABASE cabinet_medical CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Si votre MySQL a un mot de passe pour `root`, renseignez `DB_PASSWORD` dans `.env`. Puis :

```bash
php artisan migrate:fresh --seed   # tables + données de démonstration fictives
composer run dev                   # lance le serveur et Vite
```

Si `composer run dev` ne fonctionne pas sur votre machine, ouvrez deux terminaux : `php artisan serve` dans l'un,
`npm run dev` dans l'autre. L'application est sur http://localhost:8000.

## Comptes de démonstration

| Rôle       | E-mail                    | Mot de passe |
| ---------- | ------------------------- | ------------ |
| Médecin    | `medecin@cabinet.test`    | `password`   |
| Secrétaire | `secretaire@cabinet.test` | `password`   |
| Patient    | `patient@cabinet.test`    | `password`   |

Toutes les données (patients, dossiers, rendez-vous) sont fictives et générées par les seeders.
`php artisan migrate:fresh --seed` remet la base à zéro à tout moment.

## Tests

```bash
php artisan test
```

Les tests utilisent une base SQLite en mémoire : ils ne touchent pas à votre base MySQL.
Ils tournent aussi automatiquement sur GitHub (onglet Actions) à chaque push.

## Travailler à deux

1. Toujours partir d'une `main` à jour : `git checkout main && git pull`.
2. Créer une branche par fonctionnalité : `git checkout -b feature/patients`.
3. Faire de petits commits : `git commit -m "feat: liste des patients"`.
4. Pousser la branche et ouvrir une pull request ; l'autre membre du binôme relit avant la fusion.

Les conventions détaillées (rôles, modèle de données, style de code) sont dans [`CLAUDE.md`](CLAUDE.md).

## Problèmes fréquents

- `could not find driver` : activer `extension=pdo_mysql` (ou `pdo_sqlite` pour les tests) dans `php.ini`.
- `Vite manifest not found` : lancer `npm run dev` ou `npm run build`.
- `Access denied for user 'root'` : vérifier `DB_USERNAME` et `DB_PASSWORD` dans `.env`.
