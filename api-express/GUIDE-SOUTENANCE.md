# Guide de soutenance — API Express (séance du 29/11/2026)

Demandé pour la séance : le diagramme de classes imprimé, la présentation des classes, la démonstration du CRUD de
chaque classe avec **Express** et **MongoDB**. Pas d'exposé : démonstration sur machine, et **chaque étudiant explique
le code Express** et répond aux questions.

## 1. Avant la séance

- [ ] Imprimer le diagramme de classes (`2_Diagrammes_UML/04_Diagramme_de_classes.png` des livrables).
- [ ] Sur **chaque** ordinateur : `git pull`, `npm install`, `.env` avec la chaîne Atlas, `npm run dev` fonctionne.
- [ ] Tester la connexion à Atlas **sur le réseau de l'ISET**, sinon prévoir le partage de connexion d'un téléphone.
- [ ] Le matin même, Mohamed Ali seulement : `npm run seed` (il vide la base commune), puis toute la collection
  Postman (**Run**) : tout doit être vert.
- [ ] Ouvrir Atlas (*Browse Collections*) pour montrer que les données sont bien dans le cloud.
- [ ] Chacun relit sa fiche (section 7) et sait refaire seul ses requêtes Postman.

## 2. Répartition

Trois classes par membre, regroupées par partie (fixée le 27/11/2026 ; Wassim et Sofiane peuvent échanger).

| Membre               | Partie                      | Classes                                                | Fichiers à maîtriser (dans `src/`, en plus des modèles, contrôleurs et routes de ses classes) | Dossiers Postman |
| -------------------- | --------------------------- | ------------------------------------------------------ | -------------------------------------------------------------------------- | ---------------- |
| Mohamed Ali Sahlaoui | Authentification et comptes | Utilisateur (`seConnecter()`, jeton JWT), Patient, Medecin | `server.js`, `app.js`, `config/db.js`, `middlewares/authentification.js`, `utils/jeton.js` | 00, 01, 03, 04 |
| Houssem Ghandri      | Rendez-vous                 | RendezVous, HistoriqueEtat, Notification               | `models/enums.js`                                                          | 08, 09, 11, 13   |
| Wassim Ibn Rhouma    | Planning du médecin         | Specialite, Disponibilite, Indisponibilite             | `middlewares/erreurs.js`, `utils/choisir.js`, `models/validateurs.js`      | 02, 06, 07       |
| Sofiane Haj Smail    | Suivi médical               | DossierMedical, Consultation (ordonnance PDF), ParametresCabinet | `utils/dates.js`                                                 | 05, 10, 12       |

Tout le monde doit savoir expliquer le trajet d'une requête (section 3) : c'est la question la plus probable.

## 3. Le trajet d'une requête

Exemple : Postman envoie `POST http://localhost:3000/api/specialites` avec `{ "libelle": "Ophtalmologie" }`.

```text
Postman
  │  requête HTTP : méthode POST, URL /api/specialites, corps JSON
  ▼
server.js                 lit .env, se connecte à Atlas (connecterBase), puis app.listen(3000)
  ▼
src/app.js                express.json()  → transforme le corps JSON en objet : req.body
                          morgan('dev')   → affiche « POST /api/specialites 201 » dans le terminal
                          app.use('/api', routes)
  ▼
src/routes/index.js       router.use('/specialites', specialiteRoutes)
  ▼
src/routes/specialiteRoutes.js   router.post('/', controleur.creer)
  ▼
src/controllers/specialiteController.js   creer(req, res)
                          choisir(req.body, ['libelle', 'description'])   ne garde que les champs prévus
                          Specialite.create(...)   Mongoose valide le schéma puis insère dans MongoDB Atlas
                          res.status(201).json(specialite)   réponse 201 + document créé (avec son _id)
  ▼
Postman affiche la réponse JSON

En cas d'erreur (champ manquant, doublon, throw new ErreurApi(404, …)) :
Express 5 transmet l'erreur à src/middlewares/erreurs.js → gestionErreurs → 400, 404 ou 409 + message en français
```

## 4. Notions Express à connaître

| Notion                    | Dans le projet                                                                                 |
| ------------------------- | ---------------------------------------------------------------------------------------------- |
| Application Express       | `const app = express()` dans `app.js` ; `app.listen(PORT)` dans `server.js`                     |
| Middleware                | Fonction `(req, res, next)` exécutée pour chaque requête, **dans l'ordre de déclaration** : `express.json()`, `morgan`, puis les routes, puis les erreurs |
| Router                    | Groupe de routes d'une ressource (`Router()`), monté sur un préfixe avec `router.use('/specialites', …)` |
| Route                     | Méthode HTTP + chemin + fonction : `router.get('/:id', controleur.afficher)`                    |
| `req.params`              | Partie variable de l'URL : `/api/patients/:id` → `req.params.id`                               |
| `req.query`               | Paramètres après le `?` : `/api/rendez-vous?etat=CONFIRME` → `req.query.etat`                   |
| `req.body`                | Corps JSON de la requête (rempli par `express.json()`)                                          |
| `res.status().json()`     | Code HTTP + réponse JSON ; `res.type('application/pdf')` pour l'ordonnance                      |
| Fonctions `async`         | Chaque accès à MongoDB prend du temps : `await Specialite.find()` attend la réponse sans bloquer le serveur |
| Erreurs en Express 5      | Une erreur levée dans une fonction `async` est transmise automatiquement au gestionnaire d'erreurs (en Express 4, il fallait des `try/catch` partout) |
| Gestionnaire d'erreurs    | Middleware à **4 paramètres** `(err, req, res, next)`, déclaré en dernier                       |
| Ordre des routes          | Une route fixe (`/connexion`) est déclarée avant une route avec paramètre (`/:id`)              |
| REST                      | Une URL par ressource, l'action est donnée par la méthode : GET lire, POST créer, PUT modifier, PATCH modifier une partie, DELETE supprimer |

## 5. Notions MongoDB et Mongoose

**MongoDB** est une base **NoSQL orientée documents** : une base contient des **collections**, qui contiennent des
**documents** au format JSON (BSON). Chaque document a un identifiant `_id` (ObjectId de 24 caractères hexadécimaux).

| SQL (application Laravel) | MongoDB (cette API)                   |
| ------------------------- | ------------------------------------- |
| Base de données           | Base de données (`cabinet_medical`)   |
| Table                     | Collection (`specialites`)            |
| Ligne                     | Document `{ _id, libelle, … }`        |
| Clé primaire              | `_id` (ObjectId, généré automatiquement) |
| Clé étrangère             | Champ ObjectId + `ref` (non vérifié par MongoDB : d'où `doitExister`) |
| Jointure                  | `populate()` de Mongoose              |
| Migration                 | Schéma Mongoose (MongoDB n'impose aucun schéma lui-même) |

**MongoDB Atlas** : MongoDB hébergé dans le cloud. Offre gratuite M0 : 512 Mo, données répliquées sur 3 serveurs,
accessible par tous les membres du groupe sans rien installer.

**Mongoose** ajoute à MongoDB ce qu'il n'impose pas : un schéma et sa validation.

| Mongoose                         | Rôle                                                                 |
| -------------------------------- | -------------------------------------------------------------------- |
| `new mongoose.Schema({...})`     | Champs, types et règles : `required`, `unique`, `enum`, `match`, `min`, `max`, `default`, `trim`, `validate` |
| `mongoose.model('Specialite', schema, 'specialites')` | Le modèle : la classe qui lit et écrit dans la collection |
| `find()`, `findById()`, `findOne()` | Lire                                                              |
| `create()`, `new Modele()` + `save()` | Créer (avec validation)                                         |
| `document.set()` + `save()`      | Modifier (avec validation)                                           |
| `deleteOne()`, `deleteMany()`    | Supprimer                                                            |
| `countDocuments()`, `exists()`   | Compter, tester l'existence                                          |
| `populate('specialite')`         | Remplace l'identifiant par le document référencé                     |
| `schema.methods.nom = function`  | Méthode d'instance : les opérations du diagramme                     |
| `schema.pre('save', fn)`         | « Hook » exécuté avant chaque enregistrement (hachage du mot de passe) |
| `discriminator()`                | Héritage : sous-classe dans la même collection                       |

## 6. Du diagramme de classes au code

| Élément du diagramme                                  | Traduction dans le code                                               |
| ----------------------------------------------------- | -------------------------------------------------------------------- |
| Classe                                                | Un schéma + un modèle (`src/models/Classe.js`) + une collection       |
| Attribut typé                                         | Champ du schéma : `String`, `Date`, `Number`, `Boolean`, `ObjectId`  |
| `{unique}`                                            | `unique: true` (index unique de MongoDB → 409 en cas de doublon)     |
| Énumération (Role, EtatRendezVous…)                   | `src/models/enums.js` + option `enum` du schéma                       |
| Héritage Utilisateur ← Patient, Medecin               | Discriminateurs Mongoose, clé `role` : une seule collection `utilisateurs` |
| Association « exerce » Medecin 0..* → 1 Specialite    | `specialite: { type: ObjectId, ref: 'Specialite', required: true }`  |
| Multiplicité 0..1 (DossierMedical, Consultation)      | `unique: true` sur la référence : au plus un dossier par patient      |
| Composition (◆)                                       | Référence vers le parent + suppression en cascade dans le contrôleur du parent |
| Opération (`seConnecter()`, `chevauche()`…)            | `schema.methods` : appelée sur un document (`rendezVous.chevauche(autre)`) |

Choix des **références** plutôt que des documents imbriqués : chaque classe a son propre CRUD (demandé par la
séance), les documents sont consultés séparément (le dossier médical sans le patient), et les listes qui grandissent
(rendez-vous, notifications, historique) ne gonflent pas un document parent.

| Opération du diagramme   | Où                         | Route                                  |
| ------------------------ | -------------------------- | -------------------------------------- |
| `seConnecter()`          | `models/Utilisateur.js`    | `POST /api/utilisateurs/connexion`     |
| `chevauche()`            | `models/RendezVous.js`     | utilisée à la création et au déplacement d'un rendez-vous |
| `changerEtat(e)`         | `models/RendezVous.js`     | `PATCH /api/rendez-vous/:id/etat`      |
| `genererOrdonnancePDF()` | `models/Consultation.js`   | `GET /api/consultations/:id/ordonnance` |

## 7. Fiches par membre

### Mohamed Ali Sahlaoui — Authentification et comptes (Utilisateur, Patient, Medecin)

**Démo** : dossier 00 (l'API répond), 01 (créer une secrétaire, lister tous les comptes, filtre `?role=MEDECIN`,
connexion avec jeton, route protégée avec et sans jeton, mauvais mot de passe), 03 et 04. Montrer dans Atlas la
collection `utilisateurs` : le champ `role` et le mot de passe haché (`$2b$10$…`). Coller le jeton sur
[jwt.io](https://jwt.io) pour montrer son contenu (`sub`, `role`, `exp`).

**À expliquer** :
- `server.js` : `dotenv` lit `.env`, `connecterBase()` se connecte, puis `app.listen()`.
- Héritage : `Utilisateur.discriminator('Patient', patientSchema, 'PATIENT')`. Un patient est un utilisateur avec des
  champs en plus ; `Patient.find()` ne renvoie que les documents `role: 'PATIENT'`.
- `pre('save')` : `bcrypt.hash(motDePasse, 10)` seulement si le mot de passe a changé (`isModified`).
- `select: false` et `toJSON` : le mot de passe ne sort jamais dans une réponse.
- `seConnecter()` : `bcrypt.compare()` + compte actif (RG-02). Si elle réussit, `creerJeton()` (`utils/jeton.js`) signe
  un jeton JWT avec `JWT_SECRET` : il contient l'identifiant (`sub`) et le rôle, et expire au bout de 2 h.
- `exigerConnexion` (`middlewares/authentification.js`) : lit l'en-tête `Authorization: Bearer <jeton>`, vérifie la
  signature et l'expiration (`verifierJeton()`), recharge le compte (refusé s'il est désactivé) et le met dans
  `req.utilisateur`. Il est placé avant le contrôleur : `router.get('/moi', exigerConnexion, controleur.moi)`.

**Questions probables** :
- *Pourquoi une seule collection pour tous les utilisateurs ?* Les attributs communs sont au même endroit, l'e-mail est
  unique pour tous les comptes, et la connexion cherche dans une seule collection, quel que soit le rôle.
- *Pourquoi créer une secrétaire avec `new Utilisateur()` + `save()` ?* Avec `role` comme clé de discriminateur,
  `Utilisateur.create({ role: 'SECRETAIRE' })` cherche une sous-classe « SECRETAIRE », qui n'existe pas.
- *Peut-on retrouver un mot de passe à partir de son empreinte ?* Non, bcrypt est à sens unique et ajoute un sel
  aléatoire : deux mots de passe identiques donnent deux empreintes différentes. On compare avec `bcrypt.compare()`.
- *Et si le JSON contient `"role": "ADMINISTRATEUR"` à la création d'un patient ?* Ignoré : `choisir()` ne garde que les
  champs prévus, et le discriminateur impose `PATIENT`.
- *Pourquoi `String(email)` dans la connexion ?* Sans cela, envoyer `{ "email": { "$ne": null } }` deviendrait une
  condition MongoDB « e-mail différent de null » (injection NoSQL).
- *Qu'est-ce qu'un jeton JWT ?* Trois parties séparées par des points : en-tête (algorithme), contenu (`sub`, `role`,
  `exp`) et signature. Le contenu est lisible par tous (encodé, pas chiffré) : on n'y met jamais de mot de passe. La
  signature, calculée avec `JWT_SECRET`, empêche de le modifier : un rôle changé à la main rend le jeton invalide.
- *Pourquoi un jeton plutôt qu'une session ?* L'API ne garde rien en mémoire entre deux requêtes (sans état) : le jeton
  suffit pour savoir qui appelle, ce qui convient à Postman ou à une application mobile.
- *Comment se déconnecter ?* Le client oublie le jeton ; il expire de toute façon au bout de 2 h (`JWT_DUREE`).
- *Pourquoi les routes CRUD ne demandent pas de jeton ?* Pour la démonstration du CRUD de chaque classe. Étape suivante :
  ajouter `exigerConnexion` sur chaque route et un middleware qui vérifie `req.utilisateur.role`, comme les Policies
  de l'application Laravel.
- *Pourquoi refuser de supprimer un patient qui a des rendez-vous ?* Ce sont des traces de soins ; on désactive le
  compte (`actif: false`) à la place.

### Houssem Ghandri — Rendez-vous (RendezVous, HistoriqueEtat, Notification)

**Démo** : dossiers 08, 09 et 11, puis dans le dossier 13 : « Rendez-vous qui chevauche → 409 », « Transition d'état
interdite → 409 », « Supprimer un rendez-vous qui a une consultation → 409 ».

**À expliquer** :
- `chevauche(autre)` : `this.debut < autre.fin && this.fin > autre.debut` ; `trouverConflit()` fait la même chose
  dans une requête, pour le médecin **ou** le patient, en ignorant les rendez-vous annulés ou terminés (`ETATS_ACTIFS`).
- `TRANSITIONS` (`enums.js`) et `changerEtat()` : transition vérifiée (RG-17), ligne d'historique validée **avant**
  de modifier le rendez-vous, puis les deux sont enregistrées (RG-23).
- L'état n'est pas dans les champs de `PUT` : il ne change que par `PATCH /:id/etat`, pour garder l'historique.
- Suppression : refusée s'il y a une consultation ; sinon historique (composition) et notifications supprimés avec.
- Filtres : `GET /api/rendez-vous?medecin=…&etat=CONFIRME` → `choisir(req.query, [...])` devient le filtre de `find()`.

**Questions probables** :
- *Pourquoi PATCH et pas PUT pour l'état ?* On modifie une seule partie de la ressource, avec une règle métier et un
  historique : c'est une action à part.
- *Deux secrétaires réservent le même créneau à la même seconde ?* La vérification puis l'enregistrement ne sont pas
  atomiques : en théorie les deux peuvent passer. Solution : une transaction MongoDB (Atlas les permet) ou un index
  unique sur `{ medecin, debut }` si les créneaux sont fixes. Risque faible dans un cabinet.
- *Pourquoi Annulé est un état final ?* Diagramme d'états (RG-17) : on reprend un nouveau rendez-vous.
- *Pourquoi Postman affiche `08:00:00.000Z` pour un rendez-vous à 9 h ?* MongoDB enregistre en UTC ; Djerba est à UTC+1.
- *À quoi sert la route POST de l'historique si changerEtat le remplit ?* À démontrer le CRUD de la classe ; en
  fonctionnement normal, seul `changerEtat()` écrit l'historique.

### Wassim Ibn Rhouma — Planning du médecin (Specialite, Disponibilite, Indisponibilite)

**Démo** : dossiers 02, 06 et 07, puis dans le dossier 13 : « Libellé déjà utilisé → 409 », « Supprimer une spécialité
exercée → 409 », « Médecin inexistant → 400 », « Champs obligatoires manquants → 400 ».

**À expliquer** :
- Le CRUD complet de `specialiteController.js` : c'est le modèle de tous les autres contrôleurs.
- `choisir()` : la liste blanche des champs acceptés.
- `middlewares/erreurs.js` : `ValidationError` → 400 avec une erreur par champ, `CastError` → 400, code `11000`
  (index unique) → 409, `ErreurApi` → son code.
- `FORMAT_HEURE` (expression régulière `HH:MM`) et le validateur `heureFin > heureDebut` : au format `HH:MM`, l'ordre
  alphabétique est l'ordre des heures.
- `trouverConflit()` de Disponibilite : deux plages du même médecin, le même jour, se chevauchent si
  `debut1 < fin2` et `fin1 > debut2` (`$lt`, `$gt` en MongoDB) → 409.
- `doitExister()` : MongoDB ne vérifie pas les références, ce validateur le fait (`Médecin introuvable.` → 400).
- Compositions avec Medecin : suppression en cascade dans `medecinController.supprimer`.

**Questions probables** :
- *`unique: true` est-il une validation ?* Non, c'est un **index** MongoDB : la base refuse le doublon (erreur 11000),
  que le gestionnaire d'erreurs traduit en 409.
- *Pourquoi refuser de supprimer une spécialité exercée ?* Chaque médecin doit avoir une spécialité (`required`) :
  la supprimer laisserait des médecins avec une référence vers rien.
- *Pourquoi les heures des disponibilités sont du texte et pas des dates ?* Une disponibilité revient chaque semaine
  (« le lundi de 8 h 30 à 12 h 30 ») : elle n'a pas de date.
- *Pourquoi `findById()` + `save()` plutôt que `findByIdAndUpdate()` ?* `save()` relance toute la validation du schéma
  et les hooks ; `findByIdAndUpdate()` les saute par défaut.

### Sofiane Haj Smail — Suivi médical (DossierMedical, Consultation, ParametresCabinet)

**Démo** : dossiers 05, 10 (avec l'**ordonnance PDF** affichée dans Postman) et 12, puis dans le dossier 13 : « Second
dossier pour le même patient → 409 ».

**À expliquer** :
- DossierMedical : `unique: true` sur `patient` (multiplicité 0..1), groupe sanguin en `enum`, composition avec Patient
  (supprimé dans `patientController.supprimer`).
- Consultation : `unique: true` sur `rendezVous` (0..1), `populate` imbriqué (rendez-vous → patient et médecin →
  spécialité), `genererOrdonnancePDF()` qui écrit le PDF directement dans la réponse (`pdf.pipe(res)`).
- ParametresCabinet : document unique (`exists({})` → 409), valeurs par défaut, validateur qui compare deux champs avec
  `this` (fermeture après ouverture).

**Questions probables** :
- *Pourquoi ne pas mettre le dossier médical dans le document du patient ?* Les données médicales sont réservées aux
  médecins (comme dans l'application Laravel), et la séance demande un CRUD par classe.
- *Pourquoi PUT ne change pas le patient d'un dossier ?* `CHAMPS_MODIFICATION` ne contient pas `patient`.
- *Comment le PDF arrive dans Postman ?* `res.type('application/pdf')` annonce un PDF, `pdf.pipe(res)` envoie le
  fichier au fur et à mesure de sa création, `pdf.end()` termine la réponse.
- *Une consultation pour un rendez-vous inexistant ?* 400 « Rendez-vous introuvable. » (validateur `doitExister`).
- *Que renvoie `GET /api/parametres-cabinet` ?* Un tableau de 0 ou 1 élément, de la même forme que les autres listes.

## 8. Questions générales

- **Qu'est-ce qu'une API REST ?** Un serveur qui expose des ressources par des URL et répond en JSON ; l'action est
  donnée par la méthode HTTP, le résultat par le code de réponse. Pas de pages HTML : un client (Postman, application
  mobile, site) consomme les données.
- **Pourquoi Express ?** Framework minimal et très répandu pour Node.js : routes, middlewares, rien d'imposé.
- **Pourquoi MongoDB plutôt que MySQL ?** Demandé par la séance. Ses atouts : documents JSON proches des objets
  JavaScript, schéma souple, hébergement cloud gratuit avec Atlas. Ses limites : pas de clés étrangères ni de jointures
  natives (d'où `doitExister` et `populate`).
- **Qu'est-ce qu'un middleware ?** Une fonction qui reçoit la requête avant la route : `express.json()` lit le corps,
  `morgan` journalise, `gestionErreurs` traite les erreurs.
- **À quoi sert `.env` ?** À garder les secrets (chaîne Atlas avec mot de passe) hors du code et hors de GitHub
  (`.gitignore`) ; `dotenv` les met dans `process.env`.
- **`package.json`, `package-lock.json`, `node_modules` ?** Liste des dépendances et des scripts (`npm run dev`) ;
  versions exactes installées ; dossier des paquets, jamais commité (recréé par `npm install`).
- **201 et 200 ?** 201 = ressource créée (POST), 200 = succès.
- **Comment testez-vous ?** Postman (collection de 82 requêtes avec vérifications) et `npm test` (59 tests automatiques),
  lancés par GitHub Actions à chaque push avec un serveur MongoDB.
- **Comment l'API sait-elle qui est connecté ?** La connexion renvoie un jeton JWT ; le middleware `exigerConnexion`
  le vérifie sur les routes protégées (`GET /api/utilisateurs/moi`). Les routes CRUD restent ouvertes pour la
  démonstration ; étape suivante : les protéger et vérifier le rôle.
- **Lien avec l'application Laravel ?** Mêmes classes et mêmes règles (RG-02, RG-12, RG-17, RG-23), autre technologie
  et autre base. Cette API pourrait servir de back-end à une application mobile.

## 9. Déroulé conseillé (environ 12 minutes)

1. **Mohamed Ali** (2 min) : le diagramme imprimé, l'arborescence du projet, `npm run dev`, Atlas, `GET /api`.
2. **Chaque membre** (2 à 3 min) : ses dossiers Postman, le modèle puis le contrôleur d'une de ses classes, et une
   règle métier (erreur attendue).
3. **Fin** (1 min) : la collection complète dans le Runner (tout vert) et `npm test`.
