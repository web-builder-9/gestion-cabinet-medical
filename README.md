# Gestion d'un cabinet médical

Projet d'intégration de 3e année de Licence en développement web et multimédia (ISET Djerba, 2026-2027).
Application web d'un cabinet médical : prise de rendez-vous en ligne, agenda des médecins, dossiers patients,
consultations et ordonnances, administration du cabinet.

L'application suit les livrables présentés à l'encadrante : cahier des charges (30 besoins fonctionnels BF,
23 règles de gestion RG, 14 besoins non fonctionnels BNF), diagrammes UML et 17 maquettes.

Équipe : Mohamed Ali Sahlaoui, Houssem Ghandri, Wassim Ibn Rhouma, Soufienne Hadj Ismail.
Encadrante : Mme Mouna Kort.

## Fonctionnalités par rôle

| Rôle               | Ce qu'il peut faire                                                                                                                                                                                                                                     |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Visiteur           | Voir les médecins, leur spécialité et leur prochain créneau ; chercher par nom ou spécialité (BF-01, BF-02) ; créer son compte patient (BF-03).                                                                                                         |
| Patient            | Prendre rendez-vous en 3 étapes (BF-07, BF-08) ; modifier ou annuler jusqu'à 24 h avant (BF-09, RG-16) ; suivre ses rendez-vous (BF-10) ; lire ses comptes rendus et télécharger ses ordonnances (BF-11) ; modifier son profil et son mot de passe (BF-05). |
| Secrétaire         | Gérer les dossiers patients (BF-12) ; prendre, confirmer, déplacer, annuler un rendez-vous (BF-13, BF-14) ; planning du jour et de la semaine de tous les médecins (BF-15) ; marquer un patient absent (BF-16).                                            |
| Médecin            | Définir sa semaine type et ses indisponibilités (BF-17, BF-18) ; agenda jour et semaine (BF-19) ; dossier médical et historique des consultations (BF-20) ; enregistrer une consultation (BF-21) ; ordonnance PDF (BF-22).                                |
| Administrateur     | Comptes du personnel et rôles (BF-23, BF-24) ; spécialités (BF-25) ; paramètres du cabinet (BF-26) ; statistiques du mois (BF-27).                                                                                                                     |
| Service de notification | E-mail au patient à chaque prise, confirmation, modification ou annulation (BF-28) ; rappel automatique avant le rendez-vous (BF-29).                                                                                                             |

Chaque changement d'état d'un rendez-vous est historisé avec sa date et son auteur (BF-30, RG-23).

## Stack

- Laravel 13 (PHP 8.3 minimum), MySQL en développement, SQLite en mémoire pour les tests
- Blade, Tailwind CSS 4 (plugin Vite) et Alpine.js
- Authentification : Laravel Breeze (pile Blade), interface en français
- PDF des ordonnances : `barryvdh/laravel-dompdf`
- E-mails : Laravel Mail (écrits dans les logs en local) ; rappels : planificateur de tâches Laravel
- Tests : PHPUnit (plus de 300 tests Feature) ; CI : GitHub Actions à chaque push et pull request

## Installation

Prérequis : PHP 8.3 ou plus (extensions `pdo_mysql`, `pdo_sqlite`, `mbstring`, `intl`, `zip`, `dom`), Composer,
Node.js 22 et npm, MySQL 8 ou MariaDB (XAMPP, Laragon, WAMP ou DBngin conviennent).

```bash
git clone https://github.com/web-builder-9/gestion-cabinet-medical.git
cd gestion-cabinet-medical

composer install
npm install

cp .env.example .env        # sous Windows : copy .env.example .env
php artisan key:generate
```

Créer la base de données (phpMyAdmin ou en SQL), puis renseigner `DB_PASSWORD` dans `.env` si besoin :

```sql
CREATE DATABASE cabinet_medical CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate:fresh --seed   # tables + données de démonstration fictives
composer run dev                   # serveur + Vite (ou : php artisan serve et npm run dev dans deux terminaux)
```

L'application est sur http://localhost:8000. Sans MySQL, on peut aussi utiliser SQLite : `DB_CONNECTION=sqlite` dans
`.env` (le fichier `database/database.sqlite` est créé par `php artisan migrate`).

## Comptes de démonstration

Mot de passe de tous les comptes : `password`. Toutes les données sont fictives (seeders).

| Rôle           | E-mail                    | Remarque                                                          |
| -------------- | ------------------------- | ----------------------------------------------------------------- |
| Administrateur | `admin@cabinet.test`      | Ridha Ferjani                                                     |
| Secrétaire     | `secretaire@cabinet.test` | Nadia Jlassi (`h.mabrouk@cabinet.test` : compte désactivé)        |
| Médecin        | `medecin@cabinet.test`    | Dr Salma Ben Ali, médecine générale                               |
| Médecin        | `m.saidi@cabinet.test`    | Dr Mehdi Saidi, pédiatrie                                         |
| Médecin        | `l.ammar@cabinet.test`    | Dr Leila Ammar, cardiologie                                       |
| Patiente       | `patient@cabinet.test`    | Amira Ben Salah : rendez-vous à venir, historique, compte rendu   |

`php artisan migrate:fresh --seed` remet la base à zéro à tout moment (les dates des rendez-vous de démonstration
sont calculées à partir du jour où la commande est lancée).

### Parcours de démonstration conseillé

1. **Visiteur** : page d'accueil, recherche « pédiatrie », bouton « Prendre rendez-vous » (demande de connexion).
2. **Patiente** (`patient@cabinet.test`) : « Prendre rendez-vous », choisir un créneau, envoyer la demande : elle est
   « En attente ». Sur « Mes rendez-vous » : modifier, annuler, dernier compte rendu, ordonnance PDF.
3. **Secrétaire** : tableau de bord, « Demandes en ligne à confirmer » → Confirmer ; nouveau rendez-vous sur un créneau
   déjà pris pour voir le refus (RG-12) et les créneaux proposés ; planning de la semaine.
4. **Médecin** (`medecin@cabinet.test`) : agenda, « Mes disponibilités » (essayer une plage qui chevauche), fiche d'un
   patient, « Consultations » → Commencer, puis « Enregistrer et imprimer l'ordonnance ».
5. **Administrateur** : créer un compte (invitation par e-mail), désactiver Dr Saidi (refus RG-08 s'il a des
   rendez-vous), spécialités et paramètres, activité du mois.

## E-mails et rappels

En local, `MAIL_MAILER=log` : les e-mails (invitations, confirmations, rappels) sont écrits dans
`storage/logs/laravel.log` au lieu d'être envoyés. Le journal des e-mails d'un rendez-vous est aussi visible sur sa fiche.

```bash
php artisan rendez-vous:rappels   # envoie les rappels des rendez-vous confirmés des prochaines 24 h
php artisan schedule:work         # lance le planificateur en local (rappels toutes les heures)
```

## Routes

URL en français, noms de routes en anglais (conventions Laravel). Chaque groupe est protégé par le middleware `role`,
puis chaque action par sa Policy (`app/Policies`).

### Public et compte

| Méthode | URL                               | Nom                 | Accès         | Rôle de la page                                 |
| ------- | --------------------------------- | ------------------- | ------------- | ----------------------------------------------- |
| GET     | `/`                               | `home`              | tous          | Médecins, recherche, prochain créneau (maq. 1)  |
| GET/POST| `/inscription`                    | `register`          | invité        | Création d'un compte patient (maq. 2)           |
| GET/POST| `/login`                          | `login`             | invité        | Connexion (maq. 3)                              |
| GET/POST| `/forgot-password`, `/reset-password/{token}` | `password.*` | invité | Mot de passe oublié, invitation             |
| GET     | `/dashboard`                      | `dashboard`         | connecté      | Page d'arrivée selon le rôle                    |
| GET/PATCH| `/profile`                       | `profile.*`         | connecté      | Profil et mot de passe                          |

### Espace patient (`role:patient`)

| Méthode | URL                                     | Nom                     | Rôle de la page                              |
| ------- | --------------------------------------- | ----------------------- | -------------------------------------------- |
| GET     | `/dashboard`                            | `dashboard`             | Mes rendez-vous (maq. 6)                     |
| GET/POST| `/prendre-rendez-vous`                  | `booking.create/store`  | Médecin puis créneau (maq. 5)                |
| GET/PUT | `/mes-rendez-vous/{rdv}/modifier`       | `booking.edit/update`   | Modifier (remet « En attente »)              |
| PATCH   | `/mes-rendez-vous/{rdv}/annuler`        | `booking.cancel`        | Annuler (jusqu'à 24 h avant)                 |
| GET     | `/mes-comptes-rendus`                   | `my-consultations.index`| Comptes rendus en lecture seule              |

### Secrétariat et médecins (`role:doctor,secretary`)

| Méthode | URL                                       | Nom                                   | Rôle de la page                                  |
| ------- | ----------------------------------------- | ------------------------------------- | ------------------------------------------------ |
| GET     | `/dashboard`                              | `dashboard`                           | Tableau de bord du secrétariat (maq. 7)          |
| GET     | `/patients`                               | `patients.index`                      | Liste et recherche (maq. 8)                      |
| GET/POST| `/patients/create`, `/patients`           | `patients.create/store`               | Nouveau dossier (maq. 9)                         |
| GET     | `/patients/{patient}`                     | `patients.show`                       | Dossier patient (maq. 13)                        |
| GET/PUT | `/patients/{patient}/edit`                | `patients.edit/update`                | Modifier l'identité                              |
| DELETE  | `/patients/{patient}`                     | `patients.destroy`                    | Archiver (médecin)                               |
| GET     | `/patients/archives`                      | `patients.archived`                   | Dossiers archivés (médecin)                      |
| PATCH   | `/patients/{patient}/restore`             | `patients.restore`                    | Restaurer (médecin)                              |
| GET/PUT | `/patients/{patient}/dossier/edit`        | `patients.medical-record.*`           | Dossier médical (médecin)                        |
| POST    | `/patients/{patient}/invitation`          | `patients.invite`                     | Envoyer l'accès à l'espace patient               |
| GET     | `/rendez-vous`                            | `appointments.index`                  | Liste filtrée des rendez-vous                    |
| GET/POST| `/rendez-vous/nouveau`, `/rendez-vous`    | `appointments.create/store`           | Nouveau rendez-vous (maq. 10)                    |
| GET     | `/rendez-vous/{rdv}`                      | `appointments.show`                   | Fiche, historique des états, e-mails envoyés     |
| GET/PUT | `/rendez-vous/{rdv}/deplacer`             | `appointments.edit/update`            | Déplacer                                         |
| PATCH   | `/rendez-vous/{rdv}/confirmer`            | `appointments.confirm`                | En attente → Confirmé                            |
| PATCH   | `/rendez-vous/{rdv}/annuler`              | `appointments.cancel`                 | → Annulé                                         |
| PATCH   | `/rendez-vous/{rdv}/absent`               | `appointments.no-show`                | Confirmé → Absent (heure passée)                 |
| GET     | `/agenda?vue=jour\|semaine&date=&medecin=`| `agenda`                              | Agenda (maq. 11)                                 |

### Espace médecin (`role:doctor`)

| Méthode | URL                                      | Nom                               | Rôle de la page                          |
| ------- | ---------------------------------------- | --------------------------------- | ---------------------------------------- |
| GET/POST| `/disponibilites`                        | `availabilities.index/store`      | Semaine type (maq. 12)                   |
| GET/PUT/DELETE | `/disponibilites/{plage}[/modifier]` | `availabilities.edit/update/destroy` | Modifier ou supprimer une plage     |
| POST/DELETE | `/indisponibilites[/{id}]`           | `unavailabilities.store/destroy`  | Déclarer ou retirer une indisponibilité  |
| GET     | `/consultations`                         | `consultations.index`             | Patients du jour, derniers comptes rendus|
| GET/POST| `/rendez-vous/{rdv}/consultation`        | `consultations.create/store`      | Enregistrer la consultation (maq. 14)    |
| GET     | `/consultations/{consultation}`          | `consultations.show`              | Compte rendu                             |
| GET/PUT | `/consultations/{consultation}/modifier` | `consultations.edit/update`       | Modifier (auteur seulement)              |

Ordonnance (`role:doctor,patient`, patient concerné seulement) : `GET /consultations/{consultation}/ordonnance`
(`consultations.prescription`, aperçu imprimable, maq. 15) et `GET /consultations/{consultation}/ordonnance/pdf`
(`consultations.prescription.pdf`).

### Administration (`role:admin`)

| Méthode | URL                                          | Nom                                  | Rôle de la page                           |
| ------- | -------------------------------------------- | ------------------------------------ | ----------------------------------------- |
| GET/POST| `/admin/utilisateurs`                        | `admin.users.index/store`            | Comptes du personnel (maq. 16)            |
| GET/PUT | `/admin/utilisateurs/{user}/modifier`        | `admin.users.edit/update`            | Modifier un compte                        |
| PATCH   | `/admin/utilisateurs/{user}/desactiver`, `/reactiver` | `admin.users.deactivate/activate` | Désactiver (RG-08) ou réactiver |
| POST    | `/admin/utilisateurs/{user}/invitation`      | `admin.users.invite`                 | Renvoyer l'invitation                     |
| GET/PUT | `/admin/parametres`                          | `admin.settings[.update]`            | Paramètres et activité du mois (maq. 17)  |
| POST/PUT/DELETE | `/admin/specialites[/{specialite}]`  | `admin.specialties.*`                | Spécialités (RG-07)                       |

## Règles de gestion dans le code

| Règle        | Où                                                                                                    |
| ------------ | ----------------------------------------------------------------------------------------------------- |
| RG-01, RG-03 | `App\Enums\Role`, middleware `role`, `UserPolicy`, inscription publique limitée aux patients            |
| RG-02        | Validation `unique`, `LoginRequest` et middleware `EnsureAccountIsActive` (compte désactivé)          |
| RG-04        | `Password::defaults()` dans `AppServiceProvider`                                                      |
| RG-05        | `PatientRequest`, `RegisterPatientRequest` (téléphone unique, au format `22 123 456`)                 |
| RG-06, RG-07 | `Doctor` (une spécialité), `SpecialtyPolicy::delete`                                                   |
| RG-08        | `Admin\UserController::deactivate`                                                                    |
| RG-09, RG-10 | `Doctor\AvailabilityRequest`                                                                          |
| RG-11        | `App\Services\SlotFinder`                                                                             |
| RG-12 à RG-14| `App\Services\Booking` (secrétariat et espace patient)                                                |
| RG-15        | `AppointmentController::store` (Confirmé), `Patient\BookingController::store` (En attente)            |
| RG-16        | `Appointment::canBeChangedOnline()`, `AppointmentPolicy`                                              |
| RG-17        | `App\Enums\AppointmentStatus::allowedTransitions()`, `Appointment::changeStatus()`                    |
| RG-18 à RG-21| `Doctor\ConsultationController`, `ConsultationPolicy`, `MedicalRecordPolicy`                          |
| RG-22        | Pas de suppression : annulation, désactivation, `SoftDeletes` des patients ; Policies `delete` à `false` |
| RG-23        | Événements du modèle `Appointment` → table `appointment_status_changes`                               |

## Organisation du code

```
app/Enums             statuts et listes fixes (rôles, états d'un rendez-vous, jours…)
app/Http/Controllers  contrôleurs minces ; sous-dossiers Admin, Doctor, Patient, Auth
app/Http/Requests     validation (FormRequest)
app/Policies          droits de chaque action, une Policy par modèle
app/Services          logique métier : SlotFinder, Booking, DayPlanning, WeekAgenda, Statistics, AppointmentNotifier
app/Notifications     e-mails (invitation, rendez-vous, mot de passe oublié)
resources/views       vues Blade en français, composants dans components/
tests/Feature         tests par module (Admin, Appointments, Consultations, Doctor, Notifications, PatientSpace, Patients)
```

## Tests

```bash
php artisan test
php artisan test --filter=BookingTest
```

Les tests utilisent SQLite en mémoire : ils ne touchent pas à votre base. Ils tournent aussi sur GitHub (onglet Actions)
à chaque push. La matrice des droits par rôle est dans `tests/Feature/PolicyTest.php`.

## Déploiement

L'application se déploie sur Railway (MySQL, HTTPS, planificateur pour les rappels) : voir
[`docs/deploiement-railway.md`](docs/deploiement-railway.md).

## Travailler en équipe

1. Partir d'une `main` à jour : `git checkout main && git pull`.
2. Une branche par fonctionnalité : `git checkout -b feature/ma-fonctionnalite`.
3. Petits commits au format `type: description` (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`).
4. `vendor/bin/pint` puis `php artisan test` avant de pousser ; pull request relue par un autre membre, CI au vert.

Le suivi des tâches se fait dans Jira : le fichier [`docs/jira-backlog.csv`](docs/jira-backlog.csv) contient le
backlog (un ticket par besoin fonctionnel) à importer. Les conventions détaillées sont dans [`CLAUDE.md`](CLAUDE.md).

## Problèmes fréquents

- `could not find driver` : activer `extension=pdo_mysql` (ou `pdo_sqlite`) dans `php.ini`.
- `Vite manifest not found` : lancer `npm run dev` ou `npm run build`.
- `Access denied for user 'root'` : vérifier `DB_USERNAME` et `DB_PASSWORD` dans `.env`.
- Le PDF ne se télécharge pas : activer les extensions `dom` et `mbstring` dans `php.ini`.
