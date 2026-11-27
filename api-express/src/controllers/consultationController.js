const { Consultation } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Consultation (1 rendez-vous → 0..1 consultation) et son ordonnance PDF.
 */
const CHAMPS_CREATION = ['rendezVous', 'date', 'motif', 'observations', 'diagnostic', 'traitement'];
const CHAMPS_MODIFICATION = ['date', 'motif', 'observations', 'diagnostic', 'traitement'];

// Rendez-vous → patient et médecin (populate imbriqué).
const AVEC_RENDEZ_VOUS = {
  path: 'rendezVous',
  select: 'debut fin patient medecin',
  populate: [
    { path: 'patient', select: 'nom prenom' },
    { path: 'medecin', select: 'nom prenom telephone specialite', populate: { path: 'specialite', select: 'libelle' } },
  ],
};

async function trouver(id) {
  const consultation = await Consultation.findById(id);
  if (!consultation) {
    throw new ErreurApi(404, 'Consultation introuvable.');
  }
  return consultation;
}

// GET /api/consultations           filtre facultatif : ?rendezVous=<id>
async function lister(req, res) {
  const consultations = await Consultation.find(choisir(req.query, ['rendezVous']))
    .sort({ date: -1 })
    .populate(AVEC_RENDEZ_VOUS);
  res.json(consultations);
}

// GET /api/consultations/:id
async function afficher(req, res) {
  const consultation = await trouver(req.params.id);
  await consultation.populate(AVEC_RENDEZ_VOUS);
  res.json(consultation);
}

// POST /api/consultations          une seconde consultation pour le même rendez-vous → 409 (index unique)
async function creer(req, res) {
  const consultation = await Consultation.create(choisir(req.body, CHAMPS_CREATION));
  res.status(201).json(consultation);
}

// PUT /api/consultations/:id
async function modifier(req, res) {
  const consultation = await trouver(req.params.id);

  consultation.set(choisir(req.body, CHAMPS_MODIFICATION));
  await consultation.save();

  res.json(consultation);
}

// DELETE /api/consultations/:id
async function supprimer(req, res) {
  const consultation = await trouver(req.params.id);

  await consultation.deleteOne();
  res.json({ message: 'Consultation supprimée.' });
}

// GET /api/consultations/:id/ordonnance   méthode genererOrdonnancePDF() du diagramme
async function ordonnance(req, res) {
  const consultation = await trouver(req.params.id);
  await consultation.populate(AVEC_RENDEZ_VOUS);

  if (!consultation.rendezVous?.patient || !consultation.rendezVous?.medecin) {
    throw new ErreurApi(409, 'Ordonnance impossible : le rendez-vous, le patient ou le médecin a été supprimé.');
  }

  res.type('application/pdf');
  res.set('Content-Disposition', `inline; filename="ordonnance-${consultation.id}.pdf"`); // affiché dans Postman ou le navigateur
  consultation.genererOrdonnancePDF(res);
}

module.exports = { lister, afficher, creer, modifier, supprimer, ordonnance };
