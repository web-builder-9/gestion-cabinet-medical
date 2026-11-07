# Gestion d'un cabinet médical

Projet d'intégration de 3e année de Licence (développement web et multimédia, ISET Djerba), réalisé en groupe de quatre.
Application web d'un cabinet médical : prise de rendez-vous en ligne, agenda des médecins, dossiers patients,
consultations et ordonnances, administration. La facturation est hors périmètre.

Référence : les livrables présentés à l'encadrante (cahier des charges avec BF-01 à BF-30, RG-01 à RG-23, BNF-01 à
BNF-14 ; diagrammes UML ; 17 maquettes). Citer le numéro du besoin ou de la règle dans les commentaires et les tests.

Le code sera présenté et défendu devant l'encadrante : privilégier un code clair et conventionnel plutôt qu'astucieux,
et expliquer brièvement chaque choix.

## Stack

- Laravel 13, PHP 8.3 minimum
- MySQL en développement ; SQLite en mémoire pour les tests (voir `phpunit.xml`)
- Blade + Tailwind CSS 4 (plugin Vite) + Alpine.js ; Laravel Breeze (pile Blade) pour l'authentification
- PDF : `barryvdh/laravel-dompdf` ; e-mails : Laravel Mail (`MAIL_MAILER=log` en local) ; rappels : planificateur
- Tests : PHPUnit, tests Feature dans `tests/Feature` (un dossier par module)
- CI : GitHub Actions (`.github/workflows/tests.yml`) à chaque push et pull request
- Déploiement : Railway (`railway/`, `docs/deploiement-railway.md`)

## Commandes

```bash
composer install
npm install
cp .env.example .env            # Windows : copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed # recrée la base avec les données de démo
composer run dev                 # serveur + Vite (ou : php artisan serve et npm run dev)
php artisan test                 # tous les tests
php artisan test --filter=BookingTest
vendor/bin/pint                  # formate le code PHP
npm run build                    # assets de production
php artisan rendez-vous:rappels  # rappels par e-mail (planifié toutes les heures)
```

Comptes de démonstration (mot de passe `password`) : `admin@cabinet.test`, `secretaire@cabinet.test`,
`medecin@cabinet.test` (aussi `m.saidi@…`, `l.ammar@…`), `patient@cabinet.test`.

## Rôles et droits d'accès

| Ressource                                    | Administrateur | Secrétaire | Médecin        | Patient              |
| -------------------------------------------- | -------------- | ---------- | -------------- | -------------------- |
| Comptes du personnel, spécialités, paramètres | oui            | non        | non            | non                  |
| Fiche patient (identité, coordonnées)        | non            | oui        | oui            | non (son profil)     |
| Archiver / restaurer une fiche               | non            | non        | oui            | non                  |
| Dossier médical, consultations               | non            | non        | oui (RG-21)    | ses comptes rendus, en lecture |
| Rendez-vous                                  | non            | oui        | oui            | les siens (RG-16)    |
| Disponibilités                               | non            | non        | les siennes    | non                  |
| Ordonnance PDF                               | non            | non        | oui            | les siennes          |

- Le rôle est dans `users.role` (enum `App\Enums\Role`), « patient » par défaut (le moins privilégié), un seul par
  compte (RG-01). `role` n'est jamais dans `Fillable` : on l'affecte explicitement (`$user->role = Role::Secretary`).
- Inscription publique réservée aux patients (`/inscription`, RG-03). Les comptes du personnel sont créés par
  l'administrateur (`/admin/utilisateurs`) sans mot de passe : la personne reçoit une invitation par e-mail
  (`AccountInvitations`). La secrétaire peut aussi envoyer l'accès à l'espace patient depuis une fiche.
- Personne ne modifie son propre rôle ni ne désactive son propre compte ; un compte désactivé ne se connecte plus (RG-02).
- Deux niveaux de protection :
  - middleware `role` sur les groupes de routes : `->middleware(['auth', 'role:doctor,secretary'])` ;
  - Policies (`app/Policies`, une par modèle, découvertes automatiquement) pour chaque action :
    `Gate::authorize('update', $patient)` dans les contrôleurs, `authorize()` dans les FormRequest,
    `@can(...)` dans les vues (menus, boutons). Jamais de `if` sur le rôle dans une vue à la place d'une Policy.
- La matrice des droits est testée dans `tests/Feature/PolicyTest.php` : la mettre à jour avec ce tableau.

## Modèle de données

Tables : `users`, `patients` et `doctors` (héritage « une table par classe » : profil 1-1 avec `users`), `specialties`,
`medical_records`, `settings` (une seule ligne, `Setting::current()`), `availabilities`, `unavailabilities`,
`appointments`, `appointment_status_changes`, `consultations`, `prescription_items`, `appointment_notifications`.

- Noms en anglais (classes, tables, colonnes, noms de routes) pour suivre les conventions Laravel ; textes affichés
  et URL en français (`/rendez-vous`, `/disponibilites`, `/admin/utilisateurs` ; routes `appointments.*`…).
- Statuts et listes fixes : enums PHP dans `app/Enums`, stockés en texte (pas d'ENUM MySQL).
- `medical_records` est séparé de `patients` pour réserver les données médicales aux médecins.
- `patients` utilise la suppression douce (`SoftDeletes`) ; rien n'est supprimé physiquement (RG-22) : un rendez-vous
  est annulé, un compte désactivé.
- Téléphones toujours stockés au format `22 123 456` (`App\Support\PhoneNumber::format`, appelé avant validation).
- Chaque patient a un dossier médical, créé vide avec la fiche (transaction).
- États d'un rendez-vous et transitions (RG-17) : `AppointmentStatus::allowedTransitions()` ; on change l'état avec
  `$appointment->changeStatus(...)`. Chaque changement est historisé par les événements du modèle (RG-23).
- Créneaux (RG-11) : `SlotFinder` (disponibilités − indisponibilités − rendez-vous actifs). Règles de prise
  (RG-12 à RG-14) : `Booking::problem()`, communes au secrétariat et à l'espace patient. Deux créneaux se chevauchent
  si `debut1 < fin2` et `fin1 > debut2` ; les rendez-vous annulés sont ignorés.

## Organisation

- `app/Http/Controllers` : `Admin/`, `Doctor/`, `Patient/`, `Auth/` et les contrôleurs partagés (patients, rendez-vous,
  agenda, ordonnance).
- `app/Services` : `SlotFinder`, `Booking`, `DayPlanning`, `WeekAgenda`, `Statistics`, `AppointmentNotifier`,
  `AccountInvitations`. `app/Support` : `Navigation` (menu par rôle), `PhoneNumber`, `Dates`, `DateParam`.
- Composants Blade : `<x-flash-status />` (messages `status`, `warning`, `error`), `<x-appointment-status />`,
  `<x-role-badge />`, `<x-doctor-card />`, `<x-agenda-block />`, `<x-icon name="…" />`, `<x-page-header />`.
- Routes (`routes/web.php`) : une route fixe (`patients/archives`, `rendez-vous/nouveau`) est déclarée avant la route
  avec paramètre du même préfixe, sinon elle serait prise pour un identifiant.

## Conventions de code

- Contrôleurs de ressource minces ; validation dans des `FormRequest` ; autorisation dans des Policies ;
  logique métier réutilisable dans `app/Services`.
- Eloquent ou Query Builder, pas de SQL brut.
- Textes : les vues sont écrites directement en français. `__()` et `lang/fr.json` servent aux vues de Breeze
  et aux textes de Laravel ; les messages de validation et les noms de champs sont dans `lang/fr/validation.php`
  (ajouter chaque nouvelle colonne dans `attributes`).
- Données de test et de démo : uniquement des factories et seeders fictifs, jamais de vraies données de patients.
- Lancer `vendor/bin/pint` avant de commiter.
- Chaque règle métier critique (droits d'accès, conflits, calculs) a un test Feature.

## Workflow Git

- `main` est protégée : pas de push direct, tout passe par une pull request relue par un autre membre du groupe,
  avec la CI au vert.
- Une branche par fonctionnalité : `feature/rendez-vous`, `fix/...`, `docs/...`.
- Petits commits, messages en français au format `type: description`
  (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`). Exemple : `feat: CRUD patients`.
- Ne jamais commiter `.env`, `vendor/`, `node_modules/`, `public/build/`.
- Suivi des tâches dans Jira (projet « Cabinet médical », clé SCRUM) ; backlog importable : `docs/jira-backlog.csv`.

## Façon de travailler

- Proposer le plan (tables, routes, vues, droits) avant de coder un nouveau module, puis attendre la validation.
- Avancer par petites étapes, un commit par étape cohérente, avec une brève explication du quoi et du pourquoi.
- Lancer `php artisan test` avant chaque commit.

## Avancement

Fait : les 30 besoins fonctionnels du cahier des charges et les 17 maquettes — accueil public et inscription, espace
patient (prise de rendez-vous en ligne, suivi, comptes rendus), secrétariat (dossiers, rendez-vous, tableau de bord,
plannings), médecin (agenda, disponibilités, consultations, ordonnance PDF), administration (comptes, spécialités,
paramètres, statistiques), e-mails et rappels, historique des états, configuration Railway.

Pistes après la soutenance : rappels par SMS, facturation (hors périmètre actuel), export des statistiques.
