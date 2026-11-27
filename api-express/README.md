# API Express du cabinet médical

API REST qui implémente le **CRUD des 12 classes du diagramme de classes** avec **Express** et **MongoDB**
(séance du 29/11/2026). La base de données est hébergée dans le cloud sur **MongoDB Atlas** ; l'API se teste avec
**Postman** (collection fournie dans [`postman/`](postman)).

Le guide pour présenter et défendre le code est dans [`GUIDE-SOUTENANCE.md`](GUIDE-SOUTENANCE.md).

| Outil                | Rôle                                                                     |
| -------------------- | ------------------------------------------------------------------------ |
| Node.js 22 ou plus   | Exécute le JavaScript côté serveur                                        |
| Express 5            | Serveur HTTP : routes, middlewares, réponses JSON                         |
| Mongoose 9           | Schémas, validation et requêtes MongoDB                                   |
| MongoDB Atlas        | Base de données dans le cloud (offre gratuite M0)                         |
| bcryptjs             | Hachage des mots de passe                                                 |
| jsonwebtoken         | Jeton de connexion (JWT) pour les routes protégées                        |
| pdfkit               | Ordonnance au format PDF                                                  |
| node:test, supertest | Tests automatiques (`npm test`), lancés aussi par GitHub Actions          |

## 1. Créer la base sur MongoDB Atlas

Une seule personne du groupe crée le cluster, puis partage la chaîne de connexion **en privé** (jamais sur GitHub).

1. Créer un compte sur [cloud.mongodb.com](https://cloud.mongodb.com) (ou se connecter avec Google).
2. **Create** / **Build a Database** → offre **Free (M0)** → fournisseur AWS, région la plus proche
   (Francfort, Paris…) → garder le nom `Cluster0` → **Create Deployment**.
3. **Utilisateur de la base** (*Security Quickstart* ou *Database Access*) : *Username and Password*, choisir un nom
   (par exemple `cabinet`) et un mot de passe **sans caractères spéciaux** (lettres et chiffres) pour éviter d'avoir à
   les encoder dans l'URL → **Create User**. Chaque membre peut avoir son propre utilisateur.
4. **Accès réseau** (*Network Access* → *Add IP Address*) : pour la démo, **Allow access from anywhere**
   (`0.0.0.0/0`), car l'adresse IP change entre la maison, l'ISET et le partage de connexion du téléphone.
5. **Chaîne de connexion** : *Database* → **Connect** → **Drivers** → Node.js. Copier la chaîne :
   `mongodb+srv://cabinet:<db_password>@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0`
6. Remplacer `<db_password>` par le mot de passe, et ajouter le nom de la base **avant le `?`** :
   `...mongodb.net/cabinet_medical?retryWrites=...`

Les données se consultent ensuite dans Atlas (*Browse Collections*) ou avec MongoDB Compass (même chaîne).

## 2. Installer et lancer

```bash
cd api-express
npm install
cp .env.example .env        # Windows : copy .env.example .env
# Ouvrir .env et coller la chaîne Atlas dans MONGODB_URI
npm run seed                # données de démonstration (vide d'abord les collections !)
npm run dev                 # démarre l'API et la relance à chaque modification
```

Le terminal affiche :

```text
Connecté à MongoDB, base « cabinet_medical »
API démarrée : http://localhost:3000/api
```

`http://localhost:3000/api` dans le navigateur liste les ressources. Chaque requête reçue s'affiche ensuite dans le
terminal (`GET /api/patients 200 41.2 ms`).

Comptes de démonstration (mot de passe `password`) : `admin@cabinet.test`, `secretaire@cabinet.test`,
`medecin@cabinet.test`, `m.saidi@cabinet.test`, `l.ammar@cabinet.test`, `patient@cabinet.test`.
`h.mabrouk@cabinet.test` est désactivé (sa connexion est refusée).

## 3. Tester avec Postman

1. Postman → **Import** → fichier `postman/cabinet-medical.postman_collection.json`.
2. La variable `baseUrl` de la collection vaut `http://localhost:3000/api`.
3. Lancer toute la collection (**Run**), ou les requêtes une par une, **dans l'ordre des dossiers** : chaque
   « Créer » garde l'identifiant obtenu dans une variable (`{{specialiteId}}`, `{{medecinId}}`…) que les requêtes
   suivantes réutilisent. Les tests de chaque requête (onglet *Test Results*) vérifient le code de réponse.
4. Le dossier **13** montre les règles métier (erreurs 400, 404 et 409 attendues), le dossier **14** supprime tout ce
   que la collection a créé, dans l'ordre inverse des dépendances.

Si une requête « Créer » répond 409 (données d'une exécution interrompue), lancer le dossier 14 ou `npm run seed`.

## Routes

Chaque classe a les cinq mêmes routes. Exemple avec les spécialités :

| Méthode  | URL                     | Action                 | Réponse                          |
| -------- | ----------------------- | ---------------------- | -------------------------------- |
| `GET`    | `/api/specialites`      | Lister                 | 200 + tableau                    |
| `GET`    | `/api/specialites/:id`  | Afficher               | 200, ou 404                      |
| `POST`   | `/api/specialites`      | Créer (corps JSON)     | 201 + document créé, ou 400, 409 |
| `PUT`    | `/api/specialites/:id`  | Modifier (champs envoyés) | 200, ou 400, 404, 409         |
| `DELETE` | `/api/specialites/:id`  | Supprimer              | 200 + message, ou 404, 409       |

| Classe            | URL                        | Routes en plus                                          |
| ----------------- | -------------------------- | ------------------------------------------------------- |
| Utilisateur       | `/api/utilisateurs`        | `POST /connexion` (seConnecter, renvoie un jeton) ; `GET /moi` (protégée par le jeton) ; filtre `?role=` |
| Patient           | `/api/patients`            |                                                         |
| Medecin           | `/api/medecins`            | filtre `?specialite=`                                   |
| Specialite        | `/api/specialites`         |                                                         |
| DossierMedical    | `/api/dossiers-medicaux`   | filtre `?patient=`                                      |
| RendezVous        | `/api/rendez-vous`         | `PATCH /:id/etat` (changerEtat) ; filtres `?medecin=&patient=&etat=` |
| Indisponibilite   | `/api/indisponibilites`    | filtre `?medecin=`                                      |
| Disponibilite     | `/api/disponibilites`      | filtre `?medecin=`                                      |
| HistoriqueEtat    | `/api/historiques-etats`   | filtre `?rendezVous=`                                   |
| Consultation      | `/api/consultations`       | `GET /:id/ordonnance` (genererOrdonnancePDF) ; filtre `?rendezVous=` |
| Notification      | `/api/notifications`       | filtres `?rendezVous=&envoyee=&type=`                   |
| ParametresCabinet | `/api/parametres-cabinet`  | un seul document (une seconde création → 409)           |

Exemple de création :

```http
POST /api/rendez-vous
Content-Type: application/json

{
  "patient": "6743f1c2a9e5b8d4c1f0a123",
  "medecin": "6743f1c2a9e5b8d4c1f0a456",
  "debut": "2026-12-14T09:00:00+01:00",
  "fin": "2026-12-14T09:30:00+01:00",
  "motif": "Baisse de la vue"
}
```

Les dates s'envoient avec le fuseau de Djerba (`+01:00`) ; MongoDB les enregistre et les renvoie en UTC
(`2026-12-14T08:00:00.000Z` = 9 h à Djerba).

## Codes de réponse

| Code | Signification                                         | Exemple                                         |
| ---- | ----------------------------------------------------- | ----------------------------------------------- |
| 200  | Succès                                                | liste, affichage, modification, suppression     |
| 201  | Ressource créée                                       | `POST /api/patients`                            |
| 400  | Données invalides (détail par champ dans `erreurs`)   | champ obligatoire manquant, identifiant mal formé |
| 401  | Connexion refusée ou jeton manquant                   | mauvais mot de passe, compte désactivé, jeton absent, faux ou expiré |
| 404  | Ressource ou route introuvable                        | `GET /api/patients/<id inconnu>`                |
| 409  | Conflit avec les données existantes                   | e-mail déjà utilisé, créneau pris, transition interdite |
| 500  | Erreur imprévue du serveur (détail dans le terminal)  |                                                 |

## Règles métier

| Règle                                   | Où                                                             | Réponse |
| --------------------------------------- | -------------------------------------------------------------- | ------- |
| E-mail, téléphone, CIN, libellé uniques | index `unique` des schémas                                     | 409     |
| Référence vers un document existant     | validateur `doitExister` (`models/validateurs.js`)             | 400     |
| Mot de passe haché, jamais renvoyé      | hook `pre('save')`, `select: false`, `toJSON` (`Utilisateur.js`) |       |
| RG-02 : compte désactivé                | `seConnecter()` et `exigerConnexion`                           | 401     |
| Route protégée par jeton JWT            | `exigerConnexion` (`middlewares/authentification.js`)          | 401     |
| RG-12 : pas de chevauchement            | `chevauche()`, `trouverConflit()` (`RendezVous.js`)            | 409     |
| RG-17 : transitions d'état              | `TRANSITIONS` (`enums.js`), `changerEtat()`                    | 409     |
| RG-23 : historique des états            | `changerEtat()` crée un `HistoriqueEtat`                       |         |
| Compositions                            | suppression en cascade (dossier, disponibilités, historique)   |         |
| Suppression d'un élément utilisé        | spécialité exercée, patient ou médecin avec rendez-vous        | 409     |

## Organisation du code

```text
api-express/
├── server.js               point d'entrée : lit .env, se connecte à MongoDB, démarre Express
├── src/
│   ├── app.js              application Express : middlewares, routes, gestion des erreurs
│   ├── config/db.js        connexion Mongoose
│   ├── routes/             un fichier de routes par classe + index.js (préfixe /api)
│   ├── controllers/        un contrôleur par classe : lister, afficher, creer, modifier, supprimer
│   ├── models/             un schéma Mongoose par classe + enums.js, validateurs.js, index.js
│   ├── middlewares/        erreurs.js (404 et erreurs), authentification.js (jeton JWT)
│   └── utils/              ErreurApi, choisir (champs autorisés), dates, jeton (JWT)
├── scripts/seed.js         données de démonstration
├── tests/                  tests node:test + supertest
└── postman/                collection Postman
```

## Tests

```bash
npm test
```

Les tests sans base (règles, erreurs d'Express) tournent partout. Les tests du CRUD ont besoin d'une base **dédiée**,
vidée avant chaque test : `MONGODB_URI_TEST` dans `.env`, dont le nom de base doit contenir `test`
(par exemple `mongodb://localhost:27017/cabinet_medical_test`, ou une base `cabinet_medical_test` sur Atlas).
Sans elle, ils sont ignorés. GitHub Actions ([`api-express.yml`](../.github/workflows/api-express.yml)) les lance à
chaque push avec un serveur MongoDB, remplit une base de démonstration et exécute toute la collection Postman.

## Dépannage le jour de la démo

Tester la connexion **sur le réseau de l'ISET avant la séance** : certains réseaux bloquent le port 27017 utilisé
par Atlas.

| Message                                                    | Cause probable et solution                                  |
| ---------------------------------------------------------- | ----------------------------------------------------------- |
| `MONGODB_URI manquant`                                     | Fichier `.env` absent : `cp .env.example .env`              |
| `bad auth : authentication failed`                         | Utilisateur ou mot de passe Atlas faux (les caractères spéciaux doivent être encodés : `@` → `%40`) |
| `Server selection timed out` / `MongoServerSelectionError` | IP non autorisée (*Network Access*) ou port bloqué : passer par le partage de connexion du téléphone |
| `querySrv ECONNREFUSED` / `ENOTFOUND _mongodb._tcp`        | DNS du réseau : ajouter `DNS_SERVEURS=8.8.8.8,1.1.1.1` dans `.env` |
| `EADDRINUSE :::3000`                                       | Port déjà pris : fermer l'autre serveur ou mettre `PORT=3001` |

Plan B sans Internet : installer MongoDB Community Server, mettre
`MONGODB_URI=mongodb://127.0.0.1:27017/cabinet_medical` dans `.env`, puis `npm run seed`.

## Sécurité

- `.env` (chaîne de connexion avec mot de passe) n'est jamais commité : seul `.env.example` l'est.
- Mots de passe hachés avec bcrypt, jamais renvoyés dans une réponse.
- Chaque contrôleur ne garde que les champs prévus (`utils/choisir.js`) : un client ne peut pas changer `_id`, `role`
  ou `etat` en les ajoutant au JSON. L'e-mail de connexion est converti en texte (pas d'injection NoSQL).
- La connexion renvoie un **jeton JWT** (identifiant et rôle, signés avec `JWT_SECRET`, valable 2 h). Le middleware
  `exigerConnexion` le vérifie : en-tête `Authorization: Bearer <jeton>`, signature, expiration, compte toujours actif.
  Il protège `GET /api/utilisateurs/moi` ; les routes CRUD restent ouvertes pour la démonstration. Étape suivante : le
  placer sur chaque route et vérifier le rôle, comme les Policies de l'application Laravel.
- Sans `JWT_SECRET` dans `.env`, une clé de développement signe les jetons (le serveur l'annonce au démarrage) ; en
  production (`NODE_ENV=production`), la clé est obligatoire.
