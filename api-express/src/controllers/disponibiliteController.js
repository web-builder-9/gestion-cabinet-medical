const { Disponibilite } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Disponibilite (composition avec Medecin).
 */
const CHAMPS_CREATION = ['jour', 'heureDebut', 'heureFin', 'dureeCreneau', 'medecin'];
const CHAMPS_MODIFICATION = ['jour', 'heureDebut', 'heureFin', 'dureeCreneau'];

const AVEC_MEDECIN = { path: 'medecin', select: 'nom prenom' };

async function trouver(id) {
  const disponibilite = await Disponibilite.findById(id);
  if (!disponibilite) {
    throw new ErreurApi(404, 'Disponibilité introuvable.');
  }
  return disponibilite;
}

// Deux plages du même médecin, le même jour, ne peuvent pas se recouvrir.
async function verifierPlageLibre(disponibilite) {
  const conflit = await disponibilite.trouverConflit();
  if (conflit) {
    throw new ErreurApi(
      409,
      `Ce médecin a déjà une disponibilité le ${conflit.jour} de ${conflit.heureDebut} à ${conflit.heureFin}.`,
    );
  }
}

// GET /api/disponibilites          filtre facultatif : ?medecin=<id>
async function lister(req, res) {
  const disponibilites = await Disponibilite.find(choisir(req.query, ['medecin']))
    .sort({ medecin: 1, heureDebut: 1 })
    .populate(AVEC_MEDECIN);
  res.json(disponibilites);
}

// GET /api/disponibilites/:id
async function afficher(req, res) {
  const disponibilite = await trouver(req.params.id);
  await disponibilite.populate(AVEC_MEDECIN);
  res.json(disponibilite);
}

// POST /api/disponibilites         { jour, heureDebut, heureFin, dureeCreneau, medecin }
async function creer(req, res) {
  const disponibilite = new Disponibilite(choisir(req.body, CHAMPS_CREATION));

  await disponibilite.validate();
  await verifierPlageLibre(disponibilite);
  await disponibilite.save();

  res.status(201).json(disponibilite);
}

// PUT /api/disponibilites/:id
async function modifier(req, res) {
  const disponibilite = await trouver(req.params.id);

  disponibilite.set(choisir(req.body, CHAMPS_MODIFICATION));
  await disponibilite.validate();
  await verifierPlageLibre(disponibilite);
  await disponibilite.save();

  res.json(disponibilite);
}

// DELETE /api/disponibilites/:id
async function supprimer(req, res) {
  const disponibilite = await trouver(req.params.id);

  await disponibilite.deleteOne();
  res.json({ message: 'Disponibilité supprimée.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
