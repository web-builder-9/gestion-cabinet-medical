const mongoose = require('mongoose');

const Utilisateur = require('./Utilisateur');
const { Role, Genre } = require('./enums');

/**
 * Classe Patient, sous-classe d'Utilisateur : elle hérite de nom, prenom, email, motDePasse,
 * telephone, role, actif et de la méthode seConnecter(), et ajoute ses propres attributs.
 * Enregistrée dans la collection « utilisateurs » avec role = PATIENT (rempli automatiquement).
 */
const patientSchema = new mongoose.Schema({
  cin: {
    type: String,
    required: [true, 'Le CIN est obligatoire.'],
    unique: true,
    sparse: true, // l'index unique ignore les comptes sans CIN (médecins, personnel)
    trim: true,
    match: [/^\d{8}$/, 'Le CIN doit contenir 8 chiffres.'],
  },
  dateNaissance: {
    type: Date,
    required: [true, 'La date de naissance est obligatoire.'],
    validate: {
      validator: (date) => date < new Date(),
      message: 'La date de naissance doit être dans le passé.',
    },
  },
  genre: {
    type: String,
    required: [true, 'Le genre est obligatoire.'],
    enum: { values: Object.values(Genre), message: 'Genre inconnu : {VALUE} (M ou F).' },
  },
  adresse: { type: String, trim: true },
  consentementDonnees: { type: Boolean, default: false },
});

module.exports = Utilisateur.discriminator('Patient', patientSchema, Role.PATIENT);
