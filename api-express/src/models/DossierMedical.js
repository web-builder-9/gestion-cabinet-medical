const mongoose = require('mongoose');

const { doitExister } = require('./validateurs');

/**
 * Classe DossierMedical : composition avec Patient (un patient a 0 ou 1 dossier ;
 * le dossier est supprimé avec son patient, voir patientController.supprimer).
 * Séparé du patient pour réserver les données médicales aux médecins (comme dans l'application Laravel).
 */
const dossierMedicalSchema = new mongoose.Schema(
  {
    patient: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Patient',
      required: [true, 'Le patient est obligatoire.'],
      unique: true, // multiplicité 0..1 : au plus un dossier par patient
      validate: doitExister('Patient', 'Patient introuvable.'),
    },
    groupeSanguin: {
      type: String,
      enum: {
        values: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        message: 'Groupe sanguin inconnu : {VALUE}.',
      },
    },
    allergies: { type: String, trim: true },
    antecedents: { type: String, trim: true },
  },
  { versionKey: false },
);

module.exports = mongoose.model('DossierMedical', dossierMedicalSchema, 'dossiers_medicaux');
