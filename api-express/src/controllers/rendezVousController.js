const { RendezVous, HistoriqueEtat, Consultation, Notification } = require('../models');
const { EtatRendezVous, ETATS_ACTIFS } = require('../models/enums');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');
const { dateHeure } = require('../utils/dates');

/**
 * Classe RendezVous. L'état n'est pas dans les champs modifiables : il change avec
 * PATCH /api/rendez-vous/:id/etat, qui vérifie la transition et l'historise.
 */
const CHAMPS_CREATION = ['debut', 'fin', 'motif', 'patient', 'medecin'];
const CHAMPS_MODIFICATION = ['debut', 'fin', 'motif']; // déplacer le rendez-vous ou changer le motif

const AVEC_PATIENT_ET_MEDECIN = [
  { path: 'patient', select: 'nom prenom telephone' },
  { path: 'medecin', select: 'nom prenom specialite', populate: { path: 'specialite', select: 'libelle' } },
];

async function trouver(id) {
  const rendezVous = await RendezVous.findById(id);
  if (!rendezVous) {
    throw new ErreurApi(404, 'Rendez-vous introuvable.');
  }
  return rendezVous;
}

// RG-12 : refuse un créneau qui chevauche un autre rendez-vous actif du médecin ou du patient.
async function verifierCreneauLibre(rendezVous) {
  const conflit = await rendezVous.trouverConflit();
  if (conflit) {
    const qui = conflit.medecin.equals(rendezVous.medecin) ? 'le médecin' : 'le patient';
    throw new ErreurApi(
      409,
      `Créneau indisponible : ${qui} a déjà un rendez-vous du ${dateHeure(conflit.debut)} au ${dateHeure(conflit.fin)}.`,
    );
  }
}

// GET /api/rendez-vous             filtres facultatifs : ?medecin=<id>&patient=<id>&etat=CONFIRME
async function lister(req, res) {
  const rendezVous = await RendezVous.find(choisir(req.query, ['medecin', 'patient', 'etat']))
    .sort({ debut: 1 })
    .populate(AVEC_PATIENT_ET_MEDECIN);
  res.json(rendezVous);
}

// GET /api/rendez-vous/:id
async function afficher(req, res) {
  const rendezVous = await trouver(req.params.id);
  await rendezVous.populate(AVEC_PATIENT_ET_MEDECIN);
  res.json(rendezVous);
}

// POST /api/rendez-vous            { debut, fin, motif, patient, medecin }   → état EN_ATTENTE
async function creer(req, res) {
  const rendezVous = new RendezVous(choisir(req.body, CHAMPS_CREATION));

  await rendezVous.validate(); // 400 si une date manque ou si le patient ou le médecin n'existe pas
  await verifierCreneauLibre(rendezVous); // 409 si le créneau est pris
  await rendezVous.save();

  res.status(201).json(rendezVous);
}

// PUT /api/rendez-vous/:id         { debut, fin, motif }
async function modifier(req, res) {
  const rendezVous = await trouver(req.params.id);

  if (!ETATS_ACTIFS.includes(rendezVous.etat)) {
    throw new ErreurApi(409, `Un rendez-vous à l'état ${rendezVous.etat} ne peut plus être modifié.`);
  }

  rendezVous.set(choisir(req.body, CHAMPS_MODIFICATION));
  await rendezVous.validate();
  await verifierCreneauLibre(rendezVous);
  await rendezVous.save();

  res.json(rendezVous);
}

// PATCH /api/rendez-vous/:id/etat  { "etat": "CONFIRME", "auteur": "<id d'un utilisateur>" }
async function changerEtat(req, res) {
  const rendezVous = await trouver(req.params.id);
  const { etat, auteur } = req.body ?? {};

  if (!Object.values(EtatRendezVous).includes(etat)) {
    throw new ErreurApi(400, `État attendu : ${Object.values(EtatRendezVous).join(', ')}.`);
  }

  const historique = await rendezVous.changerEtat(etat, auteur); // 409 si la transition est interdite (RG-17)

  res.json({ rendezVous, historique });
}

// DELETE /api/rendez-vous/:id
async function supprimer(req, res) {
  const rendezVous = await trouver(req.params.id);

  if (await Consultation.exists({ rendezVous: rendezVous._id })) {
    throw new ErreurApi(409, "Suppression impossible : ce rendez-vous a une consultation. Supprimez-la d'abord.");
  }

  // L'historique (composition) et les notifications n'ont plus de sens sans le rendez-vous.
  await HistoriqueEtat.deleteMany({ rendezVous: rendezVous._id });
  await Notification.deleteMany({ rendezVous: rendezVous._id });
  await rendezVous.deleteOne();

  res.json({ message: 'Rendez-vous supprimé, avec son historique et ses notifications.' });
}

module.exports = { lister, afficher, creer, modifier, changerEtat, supprimer };
