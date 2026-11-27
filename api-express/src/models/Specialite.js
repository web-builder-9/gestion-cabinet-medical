const mongoose = require('mongoose');

/**
 * Classe Specialite : médecine générale, pédiatrie, cardiologie…
 */
const specialiteSchema = new mongoose.Schema(
  {
    libelle: {
      type: String,
      required: [true, 'Le libellé est obligatoire.'],
      unique: true,
      trim: true,
    },
    description: { type: String, trim: true },
  },
  { versionKey: false },
);

module.exports = mongoose.model('Specialite', specialiteSchema, 'specialites');
