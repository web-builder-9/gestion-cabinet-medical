const { Specialite, Medecin } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Specialite.
 */
const CHAMPS = ['libelle', 'description'];

async function trouver(id) {
  const specialite = await Specialite.findById(id);
  if (!specialite) {
    throw new ErreurApi(404, 'Spécialité introuvable.');
  }
  return specialite;
}

// GET /api/specialites
async function lister(req, res) {
  const specialites = await Specialite.find().sort({ libelle: 1 });
  res.json(specialites);
}

// GET /api/specialites/:id
async function afficher(req, res) {
  res.json(await trouver(req.params.id));
}

// POST /api/specialites
async function creer(req, res) {
  const specialite = await Specialite.create(choisir(req.body, CHAMPS));
  res.status(201).json(specialite); // 201 Created
}

// PUT /api/specialites/:id
async function modifier(req, res) {
  const specialite = await trouver(req.params.id);

  specialite.set(choisir(req.body, CHAMPS));
  await specialite.save();

  res.json(specialite);
}

// DELETE /api/specialites/:id
async function supprimer(req, res) {
  const specialite = await trouver(req.params.id);

  // Chaque médecin doit exercer une spécialité : on ne supprime pas une spécialité utilisée.
  const nombreMedecins = await Medecin.countDocuments({ specialite: specialite._id });
  if (nombreMedecins > 0) {
    throw new ErreurApi(409, `Suppression impossible : ${nombreMedecins} médecin(s) exercent cette spécialité.`);
  }

  await specialite.deleteOne();
  res.json({ message: 'Spécialité supprimée.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
