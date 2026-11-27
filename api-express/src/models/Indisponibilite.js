const mongoose = require('mongoose');

const { doitExister } = require('./validateurs');

/**
 * Classe Indisponibilite : congé, formation… d'un médecin, de « debut » à « fin ».
 * Composition avec Medecin : supprimée avec le médecin.
 */
const indisponibiliteSchema = new mongoose.Schema(
  {
    debut: { type: Date, required: [true, 'Le début est obligatoire.'] },
    fin: {
      type: Date,
      required: [true, 'La fin est obligatoire.'],
      validate: {
        validator: function (fin) {
          return !this.debut || fin > this.debut;
        },
        message: 'La fin doit être après le début.',
      },
    },
    motif: { type: String, trim: true },
    medecin: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Medecin',
      required: [true, 'Le médecin est obligatoire.'],
      validate: doitExister('Medecin', 'Médecin introuvable.'),
    },
  },
  { versionKey: false },
);

module.exports = mongoose.model('Indisponibilite', indisponibiliteSchema, 'indisponibilites');
