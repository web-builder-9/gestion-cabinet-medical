const { HistoriqueEtat } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe HistoriqueEtat (composition avec RendezVous).
 * En fonctionnement normal, les lignes sont créées par PATCH /api/rendez-vous/:id/etat (RG-23) ;
 * les routes de création, modification et suppression servent à démontrer le CRUD de la classe.
 */
const CHAMPS_CREATION = ['rendezVous', 'ancienEtat', 'nouvelEtat', 'dateChangement', 'auteur'];
const CHAMPS_MODIFICATION = ['ancienEtat', 'nouvelEtat', 'dateChangement', 'auteur'];

const AVEC_AUTEUR = { path: 'auteur', select: 'nom prenom role' };

async function trouver(id) {
  const historique = await HistoriqueEtat.findById(id);
  if (!historique) {
    throw new ErreurApi(404, "Ligne d'historique introuvable.");
  }
  return historique;
}

// GET /api/historiques-etats       filtre facultatif : ?rendezVous=<id>
async function lister(req, res) {
  const historiques = await HistoriqueEtat.find(choisir(req.query, ['rendezVous']))
    .sort({ dateChangement: 1 })
    .populate(AVEC_AUTEUR);
  res.json(historiques);
}

// GET /api/historiques-etats/:id
async function afficher(req, res) {
  const historique = await trouver(req.params.id);
  await historique.populate(AVEC_AUTEUR);
  res.json(historique);
}

// POST /api/historiques-etats      { rendezVous, ancienEtat, nouvelEtat, auteur }
async function creer(req, res) {
  const historique = await HistoriqueEtat.create(choisir(req.body, CHAMPS_CREATION));
  res.status(201).json(historique);
}

// PUT /api/historiques-etats/:id
async function modifier(req, res) {
  const historique = await trouver(req.params.id);

  historique.set(choisir(req.body, CHAMPS_MODIFICATION));
  await historique.save();

  res.json(historique);
}

// DELETE /api/historiques-etats/:id
async function supprimer(req, res) {
  const historique = await trouver(req.params.id);

  await historique.deleteOne();
  res.json({ message: "Ligne d'historique supprimée." });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
