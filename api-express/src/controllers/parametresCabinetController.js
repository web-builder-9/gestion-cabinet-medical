const { ParametresCabinet } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe ParametresCabinet : un seul document de réglages pour tout le cabinet.
 */
const CHAMPS = ['heureOuverture', 'heureFermeture', 'dureeCreneauDefaut', 'delaiAnnulationHeures', 'delaiReservationJours'];

async function trouver(id) {
  const parametres = await ParametresCabinet.findById(id);
  if (!parametres) {
    throw new ErreurApi(404, 'Paramètres introuvables.');
  }
  return parametres;
}

// GET /api/parametres-cabinet      tableau de 0 ou 1 élément, comme les autres listes
async function lister(req, res) {
  res.json(await ParametresCabinet.find());
}

// GET /api/parametres-cabinet/:id
async function afficher(req, res) {
  res.json(await trouver(req.params.id));
}

// POST /api/parametres-cabinet     les champs absents prennent leur valeur par défaut (08:00, 18:00, 30…)
async function creer(req, res) {
  if (await ParametresCabinet.exists({})) {
    throw new ErreurApi(409, 'Les paramètres du cabinet existent déjà : modifiez-les avec PUT /api/parametres-cabinet/:id.');
  }

  const parametres = await ParametresCabinet.create(choisir(req.body, CHAMPS));
  res.status(201).json(parametres);
}

// PUT /api/parametres-cabinet/:id
async function modifier(req, res) {
  const parametres = await trouver(req.params.id);

  parametres.set(choisir(req.body, CHAMPS));
  await parametres.save();

  res.json(parametres);
}

// DELETE /api/parametres-cabinet/:id
async function supprimer(req, res) {
  const parametres = await trouver(req.params.id);

  await parametres.deleteOne();
  res.json({ message: 'Paramètres supprimés.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
