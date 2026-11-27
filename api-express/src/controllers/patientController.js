const { Patient, DossierMedical, RendezVous } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Patient (sous-classe d'Utilisateur) : le rôle PATIENT est rempli automatiquement.
 */
const CHAMPS = [
  // hérités d'Utilisateur
  'nom', 'prenom', 'email', 'motDePasse', 'telephone', 'actif',
  // propres à Patient
  'cin', 'dateNaissance', 'genre', 'adresse', 'consentementDonnees',
];

async function trouver(id) {
  const patient = await Patient.findById(id);
  if (!patient) {
    throw new ErreurApi(404, 'Patient introuvable.');
  }
  return patient;
}

// GET /api/patients
async function lister(req, res) {
  const patients = await Patient.find().sort({ nom: 1, prenom: 1 });
  res.json(patients);
}

// GET /api/patients/:id
async function afficher(req, res) {
  res.json(await trouver(req.params.id));
}

// POST /api/patients
async function creer(req, res) {
  const patient = await Patient.create(choisir(req.body, CHAMPS));
  res.status(201).json(patient);
}

// PUT /api/patients/:id
async function modifier(req, res) {
  const patient = await trouver(req.params.id);

  patient.set(choisir(req.body, CHAMPS)); // ne modifie que les champs envoyés
  await patient.save(); // relance la validation du schéma

  res.json(patient);
}

// DELETE /api/patients/:id
async function supprimer(req, res) {
  const patient = await trouver(req.params.id);

  // Les rendez-vous gardent la trace des soins : on ne supprime pas un patient qui en a.
  const nombreRendezVous = await RendezVous.countDocuments({ patient: patient._id });
  if (nombreRendezVous > 0) {
    throw new ErreurApi(
      409,
      `Suppression impossible : ce patient a ${nombreRendezVous} rendez-vous. Vous pouvez désactiver son compte (actif = false).`,
    );
  }

  // Composition : le dossier médical disparaît avec son patient.
  await DossierMedical.deleteOne({ patient: patient._id });
  await patient.deleteOne();

  res.json({ message: 'Patient supprimé, avec son dossier médical.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
