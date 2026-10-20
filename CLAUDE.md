# Gestion d'un cabinet médical

Projet d'intégration de 3e année de Licence (développement web et multimédia, ISET Djerba), réalisé en binôme.
Application web pour un cabinet médical : patients, rendez-vous, consultations, ordonnances, facturation, tableau de bord.

Le code sera présenté et défendu devant l'encadrante : privilégier un code clair et conventionnel plutôt qu'astucieux,
et expliquer brièvement chaque choix.

## Stack

- Laravel 13, PHP 8.3 minimum
- MySQL en développement ; SQLite en mémoire pour les tests (voir `phpunit.xml`)
- Blade + Tailwind CSS 4 (plugin Vite) + Alpine.js
- Authentification : Laravel Breeze (pile Blade), vues migrées de Tailwind 3 vers Tailwind 4
- Tests : PHPUnit, tests Feature dans `tests/Feature`
- CI : GitHub Actions (`.github/workflows/tests.yml`) à chaque push et pull request

## Commandes

```bash
composer install
npm install
cp .env.example .env            # Windows : copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed # recrée la base avec les données de démo
composer run dev                 # serveur + Vite (ou : php artisan serve et npm run dev)
php artisan test                 # tous les tests
php artisan test --filter=AppointmentOverlapTest
vendor/bin/pint                  # formate le code PHP
npm run build                    # assets de production
```

Comptes de démonstration (mot de passe `password`) : `medecin@cabinet.test`, `secretaire@cabinet.test`,
`patient@cabinet.test`.

## Rôles et droits d'accès

| Ressource                                 | Médecin | Secrétaire | Patient (plus tard) |
| ----------------------------------------- | ------- | ---------- | ------------------- |
| Fiche patient (identité, coordonnées)     | oui     | oui        | sa fiche            |
| Dossier médical, consultations, notes     | oui     | non        | non                 |
| Rendez-vous                               | oui     | oui        | ses rendez-vous     |
| Ordonnances                               | oui     | non        | non                 |
| Facturation                               | oui     | oui        | non                 |
| Comptes du personnel                      | oui     | non        | non                 |

- Le rôle est dans `users.role` (enum `App\Enums\Role`), « patient » par défaut (le moins privilégié).
- `role` n'est jamais dans `Fillable` : on l'affecte explicitement (`$user->role = Role::Secretary`).
- Pas d'inscription publique ni de suppression de son propre compte : les comptes du personnel sont créés et modifiés
  par le médecin dans `/personnel` (`StaffController`). Personne ne modifie son propre rôle.
- Deux niveaux de protection :
  - middleware `role` sur les groupes de routes : `->middleware(['auth', 'role:doctor,secretary'])` ;
  - Policies (`app/Policies`, une par modèle, découvertes automatiquement) pour chaque action :
    `Gate::authorize('update', $patient)` dans les contrôleurs, `authorize()` dans les FormRequest,
    `@can(...)` dans les vues (menus, boutons). Jamais de `if` sur le rôle dans une vue à la place d'une Policy.
- La matrice des droits est testée dans `tests/Feature/PolicyTest.php` : la mettre à jour avec ce tableau.

## Modèle de données

Tables faites : `users` (+ role, phone), `patients`, `medical_records`, `appointments`.
Tables à venir : `consultations`, `prescriptions`, `prescription_items`, `invoices`.

- Noms en anglais (classes, tables, colonnes, noms de routes) pour suivre les conventions Laravel ; textes affichés
  et URL en français (`/personnel`, `/patients`, `/rendez-vous` ; routes `staff.*`, `patients.*`, `appointments.*`).
- Statuts et listes fixes : enums PHP dans `app/Enums`, stockés en texte (pas d'ENUM MySQL).
- `medical_records` est séparé de `patients` pour réserver les données médicales au médecin.
- `patients` utilise la suppression douce (`SoftDeletes`) : on ne perd jamais l'historique.
- Montants en `decimal(10,3)` (le dinar a trois décimales).
- Conflits de rendez-vous : `Appointment::overlapping($doctorId, $start, $end, $ignoreId)`. Deux créneaux se chevauchent
  si `debut1 < fin2` et `fin1 > debut2` ; les rendez-vous annulés sont ignorés.

## Conventions de code

- Contrôleurs de ressource minces ; validation dans des `FormRequest` ; autorisation dans des Policies ;
  logique métier réutilisable dans `app/Services`.
- Eloquent ou Query Builder, pas de SQL brut.
- Textes : les nouvelles vues sont écrites directement en français. `__()` et `lang/fr.json` servent aux vues de Breeze
  et aux textes de Laravel ; les messages de validation et les noms de champs sont dans `lang/fr/validation.php`
  (ajouter chaque nouvelle colonne dans `attributes`).
- Données de test et de démo : uniquement des factories et seeders fictifs, jamais de vraies données de patients.
- Lancer `vendor/bin/pint` avant de commiter.
- Chaque règle métier critique (droits d'accès, conflits, calculs) a un test Feature.

## Workflow Git

- `main` est protégée : pas de push direct, tout passe par une pull request relue par l'autre membre du binôme,
  avec la CI au vert.
- Une branche par fonctionnalité : `feature/patients`, `feature/rendez-vous`, `fix/...`.
- Petits commits, messages en français au format `type: description`
  (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`). Exemple : `feat: CRUD patients`.
- Ne jamais commiter `.env`, `vendor/`, `node_modules/`, `public/build/`.

## Façon de travailler

- Proposer le plan (tables, routes, vues, droits) avant de coder un nouveau module, puis attendre la validation.
- Avancer par petites étapes, un commit par étape cohérente, avec une brève explication du quoi et du pourquoi.
- Lancer `php artisan test` avant chaque commit.

## Avancement

Fait : squelette, authentification, interface en français, rôles (middleware, Policies, menu), gestion du personnel,
modèle de données des patients et rendez-vous.

Prochaines étapes :

1. Module Patients : liste, recherche, création, modification, dossier médical (médecin seulement).
2. Module Rendez-vous : agenda jour/semaine, statuts, refus des conflits.
3. Consultations, puis ordonnances (PDF), facturation et tableau de bord.
4. Espace patient : comptes liés à une fiche par la secrétaire, consultation de ses rendez-vous.
