const { DossierMedical } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe DossierMedical (composition avec Patient : 0 ou 1 dossier par patient).
 */
const CHAMPS_CREATION = ['patient', 'groupeSanguin', 'allergies', 'antecedents'];
const CHAMPS_MODIFICATION = ['groupeSanguin', 'allergies', 'antecedents']; // un dossier ne change pas de patient

const AVEC_PATIENT = { path: 'patient', select: 'nom prenom cin dateNaissance' };

async function trouver(id) {
  const dossier = await DossierMedical.findById(id);
  if (!dossier) {
    throw new ErreurApi(404, 'Dossier médical introuvable.');
  }
  return dossier;
}

// GET /api/dossiers-medicaux       filtre facultatif : ?patient=<id>
async function lister(req, res) {
  const dossiers = await DossierMedical.find(choisir(req.query, ['patient'])).populate(AVEC_PATIENT);
  res.json(dossiers);
}

// GET /api/dossiers-medicaux/:id
async function afficher(req, res) {
  const dossier = await trouver(req.params.id);
  await dossier.populate(AVEC_PATIENT);
  res.json(dossier);
}

// POST /api/dossiers-medicaux      un second dossier pour le même patient → 409 (index unique)
async function creer(req, res) {
  const dossier = await DossierMedical.create(choisir(req.body, CHAMPS_CREATION));
  res.status(201).json(dossier);
}

// PUT /api/dossiers-medicaux/:id
async function modifier(req, res) {
  const dossier = await trouver(req.params.id);

  dossier.set(choisir(req.body, CHAMPS_MODIFICATION));
  await dossier.save();

  res.json(dossier);
}

// DELETE /api/dossiers-medicaux/:id
async function supprimer(req, res) {
  const dossier = await trouver(req.params.id);

  await dossier.deleteOne();
  res.json({ message: 'Dossier médical supprimé.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
