const mongoose = require('mongoose');

const ErreurApi = require('../utils/ErreurApi');
const { EtatRendezVous, TRANSITIONS, ETATS_ACTIFS } = require('./enums');
const { doitExister } = require('./validateurs');

/**
 * Classe RendezVous : un patient chez un médecin, de « debut » à « fin ».
 * L'état ne se modifie pas directement : on passe par changerEtat() (RG-17), qui l'historise (RG-23).
 */
const rendezVousSchema = new mongoose.Schema(
  {
    debut: { type: Date, required: [true, 'Le début est obligatoire.'] },
    fin: {
      type: Date,
      required: [true, 'La fin est obligatoire.'],
      validate: {
        // « this » est le rendez-vous en cours de validation.
        validator: function (fin) {
          return !this.debut || fin > this.debut;
        },
        message: 'La fin doit être après le début.',
      },
    },
    motif: { type: String, trim: true },
    etat: {
      type: String,
      enum: { values: Object.values(EtatRendezVous), message: 'État inconnu : {VALUE}.' },
      default: EtatRendezVous.EN_ATTENTE,
    },
    creeLe: { type: Date, default: Date.now, immutable: true },
    patient: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Patient',
      required: [true, 'Le patient est obligatoire.'],
      validate: doitExister('Patient', 'Patient introuvable.'),
    },
    medecin: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Medecin',
      required: [true, 'Le médecin est obligatoire.'],
      validate: doitExister('Medecin', 'Médecin introuvable.'),
    },
  },
  { versionKey: false },
);

/**
 * Méthode chevauche() du diagramme : deux créneaux se chevauchent si debut1 < fin2 et fin1 > debut2.
 * Exemple : 9 h-9 h 30 et 9 h 15-9 h 45 se chevauchent ; 9 h-9 h 30 et 9 h 30-10 h, non.
 */
rendezVousSchema.methods.chevauche = function (autre) {
  return this.debut < autre.fin && this.fin > autre.debut;
};

/**
 * RG-12 : cherche dans la base un rendez-vous actif (en attente ou confirmé) du même médecin
 * ou du même patient qui chevauche celui-ci. C'est la condition de chevauche() écrite en requête
 * MongoDB ($lt : strictement inférieur, $gt : strictement supérieur). Renvoie null s'il n'y en a pas.
 */
rendezVousSchema.methods.trouverConflit = function () {
  return this.constructor.findOne({
    _id: { $ne: this._id }, // pas lui-même (cas d'une modification)
    etat: { $in: ETATS_ACTIFS }, // un rendez-vous annulé ou terminé libère le créneau
    $or: [{ medecin: this.medecin }, { patient: this.patient }],
    debut: { $lt: this.fin },
    fin: { $gt: this.debut },
  });
};

/**
 * Méthode changerEtat(e) du diagramme (RG-17) : vérifie que la transition est autorisée,
 * change l'état et enregistre la ligne d'historique (RG-23).
 */
rendezVousSchema.methods.changerEtat = async function (nouvelEtat, auteur) {
  const ancienEtat = this.etat;

  if (!TRANSITIONS[ancienEtat].includes(nouvelEtat)) {
    const possibles = TRANSITIONS[ancienEtat].join(', ') || 'aucun (état final)';
    throw new ErreurApi(409, `Passage de ${ancienEtat} à ${nouvelEtat} interdit. États possibles : ${possibles}.`);
  }

  const HistoriqueEtat = mongoose.model('HistoriqueEtat');
  const historique = new HistoriqueEtat({ rendezVous: this._id, ancienEtat, nouvelEtat, auteur });
  await historique.validate(); // auteur inconnu → erreur 400 avant d'avoir changé quoi que ce soit

  this.etat = nouvelEtat;
  await this.save();
  await historique.save();

  return historique;
};

module.exports = mongoose.model('RendezVous', rendezVousSchema, 'rendez_vous');
