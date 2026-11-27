const { Indisponibilite } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Indisponibilite (composition avec Medecin) : congés, formations…
 */
const CHAMPS_CREATION = ['debut', 'fin', 'motif', 'medecin'];
const CHAMPS_MODIFICATION = ['debut', 'fin', 'motif'];

const AVEC_MEDECIN = { path: 'medecin', select: 'nom prenom' };

async function trouver(id) {
  const indisponibilite = await Indisponibilite.findById(id);
  if (!indisponibilite) {
    throw new ErreurApi(404, 'Indisponibilité introuvable.');
  }
  return indisponibilite;
}

// GET /api/indisponibilites        filtre facultatif : ?medecin=<id>
async function lister(req, res) {
  const indisponibilites = await Indisponibilite.find(choisir(req.query, ['medecin']))
    .sort({ debut: 1 })
    .populate(AVEC_MEDECIN);
  res.json(indisponibilites);
}

// GET /api/indisponibilites/:id
async function afficher(req, res) {
  const indisponibilite = await trouver(req.params.id);
  await indisponibilite.populate(AVEC_MEDECIN);
  res.json(indisponibilite);
}

// POST /api/indisponibilites       { debut, fin, motif, medecin }
async function creer(req, res) {
  const indisponibilite = await Indisponibilite.create(choisir(req.body, CHAMPS_CREATION));
  res.status(201).json(indisponibilite);
}

// PUT /api/indisponibilites/:id
async function modifier(req, res) {
  const indisponibilite = await trouver(req.params.id);

  indisponibilite.set(choisir(req.body, CHAMPS_MODIFICATION));
  await indisponibilite.save();

  res.json(indisponibilite);
}

// DELETE /api/indisponibilites/:id
async function supprimer(req, res) {
  const indisponibilite = await trouver(req.params.id);

  await indisponibilite.deleteOne();
  res.json({ message: 'Indisponibilité supprimée.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
