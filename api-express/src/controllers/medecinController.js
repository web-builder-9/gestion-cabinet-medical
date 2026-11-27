const { Medecin, Disponibilite, Indisponibilite, RendezVous } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Medecin (sous-classe d'Utilisateur) : le rôle MEDECIN est rempli automatiquement.
 */
const CHAMPS = [
  'nom', 'prenom', 'email', 'motDePasse', 'telephone', 'actif', // hérités d'Utilisateur
  'presentation', 'specialite', // propres à Medecin
];

// populate remplace l'identifiant de la spécialité par { _id, libelle }.
const AVEC_SPECIALITE = { path: 'specialite', select: 'libelle' };

async function trouver(id) {
  const medecin = await Medecin.findById(id);
  if (!medecin) {
    throw new ErreurApi(404, 'Médecin introuvable.');
  }
  return medecin;
}

// GET /api/medecins               filtre facultatif : ?specialite=<id>
async function lister(req, res) {
  const medecins = await Medecin.find(choisir(req.query, ['specialite'])).sort({ nom: 1 }).populate(AVEC_SPECIALITE);
  res.json(medecins);
}

// GET /api/medecins/:id
async function afficher(req, res) {
  const medecin = await trouver(req.params.id);
  await medecin.populate(AVEC_SPECIALITE);
  res.json(medecin);
}

// POST /api/medecins
async function creer(req, res) {
  const medecin = await Medecin.create(choisir(req.body, CHAMPS));
  res.status(201).json(medecin);
}

// PUT /api/medecins/:id
async function modifier(req, res) {
  const medecin = await trouver(req.params.id);

  medecin.set(choisir(req.body, CHAMPS));
  await medecin.save();
  await medecin.populate(AVEC_SPECIALITE);

  res.json(medecin);
}

// DELETE /api/medecins/:id
async function supprimer(req, res) {
  const medecin = await trouver(req.params.id);

  const nombreRendezVous = await RendezVous.countDocuments({ medecin: medecin._id });
  if (nombreRendezVous > 0) {
    throw new ErreurApi(
      409,
      `Suppression impossible : ce médecin a ${nombreRendezVous} rendez-vous. Vous pouvez désactiver son compte (actif = false).`,
    );
  }

  // Compositions : disponibilités et indisponibilités disparaissent avec le médecin.
  await Disponibilite.deleteMany({ medecin: medecin._id });
  await Indisponibilite.deleteMany({ medecin: medecin._id });
  await medecin.deleteOne();

  res.json({ message: 'Médecin supprimé, avec ses disponibilités et indisponibilités.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
