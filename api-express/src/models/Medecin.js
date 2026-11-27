const mongoose = require('mongoose');

const Utilisateur = require('./Utilisateur');
const { Role } = require('./enums');
const { doitExister } = require('./validateurs');

/**
 * Classe Medecin, sous-classe d'Utilisateur (role = MEDECIN).
 * Association « exerce » : un médecin exerce une spécialité (0..* → 1). On garde l'identifiant
 * de la spécialité (référence), et populate('specialite') la remplace par le document complet.
 */
const medecinSchema = new mongoose.Schema({
  presentation: { type: String, trim: true },
  specialite: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Specialite',
    required: [true, 'La spécialité est obligatoire.'],
    validate: doitExister('Specialite', 'Spécialité introuvable.'),
  },
});

module.exports = Utilisateur.discriminator('Medecin', medecinSchema, Role.MEDECIN);
