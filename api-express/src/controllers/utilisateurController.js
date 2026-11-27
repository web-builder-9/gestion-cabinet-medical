const { Utilisateur } = require('../models');
const { Role } = require('../models/enums');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Utilisateur.
 * Grâce à l'héritage, tous les comptes sont dans la même collection : la liste montre patients,
 * médecins et personnel. On crée et supprime ici les comptes du personnel (secrétaires et
 * administrateurs) ; les patients et les médecins ont leurs routes, qui gèrent aussi leurs données liées.
 */
const ROLES_PERSONNEL = [Role.SECRETAIRE, Role.ADMINISTRATEUR];

// Champs acceptés dans le JSON. Le rôle n'en fait pas partie : il est vérifié puis affecté à part.
const CHAMPS = ['nom', 'prenom', 'email', 'motDePasse', 'telephone', 'actif'];

async function trouver(id) {
  const utilisateur = await Utilisateur.findById(id);
  if (!utilisateur) {
    throw new ErreurApi(404, 'Utilisateur introuvable.');
  }
  return utilisateur;
}

// GET /api/utilisateurs            filtre facultatif : ?role=MEDECIN
async function lister(req, res) {
  const utilisateurs = await Utilisateur.find(choisir(req.query, ['role'])).sort({ nom: 1, prenom: 1 });
  res.json(utilisateurs);
}

// GET /api/utilisateurs/:id
async function afficher(req, res) {
  res.json(await trouver(req.params.id));
}

// POST /api/utilisateurs           { nom, prenom, email, motDePasse, telephone, role: SECRETAIRE | ADMINISTRATEUR }
async function creer(req, res) {
  const role = req.body?.role;
  if (!ROLES_PERSONNEL.includes(role)) {
    throw new ErreurApi(
      400,
      'Rôle attendu : SECRETAIRE ou ADMINISTRATEUR. Un patient se crée avec POST /api/patients, un médecin avec POST /api/medecins.',
    );
  }

  const utilisateur = new Utilisateur(choisir(req.body, CHAMPS));
  utilisateur.role = role;
  await utilisateur.save(); // valide les champs, puis hache le mot de passe (hook « pre save »)

  res.status(201).json(utilisateur);
}

// PUT /api/utilisateurs/:id        attributs communs de n'importe quel compte, par exemple { "actif": false }
async function modifier(req, res) {
  const utilisateur = await trouver(req.params.id);

  utilisateur.set(choisir(req.body, CHAMPS));
  await utilisateur.save();

  res.json(utilisateur);
}

// DELETE /api/utilisateurs/:id
async function supprimer(req, res) {
  const utilisateur = await trouver(req.params.id);

  if (!ROLES_PERSONNEL.includes(utilisateur.role)) {
    const route = utilisateur.role === Role.PATIENT ? '/api/patients' : '/api/medecins';
    throw new ErreurApi(409, `Ce compte est un ${utilisateur.role} : supprimez-le avec DELETE ${route}/${utilisateur.id}.`);
  }

  await utilisateur.deleteOne();
  res.json({ message: 'Utilisateur supprimé.' });
}

// POST /api/utilisateurs/connexion { email, motDePasse }   méthode seConnecter() du diagramme
async function connexion(req, res) {
  const { email, motDePasse } = req.body ?? {};

  // String() : si le client envoie un objet comme { "$ne": null } à la place de l'e-mail,
  // il devient un simple texte et non une condition MongoDB (protection contre l'injection NoSQL).
  const utilisateur = await Utilisateur.findOne({ email: String(email ?? '').trim().toLowerCase() }).select('+motDePasse');

  const connecte = utilisateur ? await utilisateur.seConnecter(motDePasse) : false;
  if (!connecte) {
    throw new ErreurApi(401, 'Connexion refusée : e-mail ou mot de passe incorrect, ou compte désactivé.');
  }

  res.json({ connecte: true, utilisateur });
}

module.exports = { lister, afficher, creer, modifier, supprimer, connexion };
