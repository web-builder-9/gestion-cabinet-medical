const mongoose = require('mongoose');

const { EtatRendezVous } = require('./enums');
const { doitExister } = require('./validateurs');

const ETATS = { values: Object.values(EtatRendezVous), message: 'État inconnu : {VALUE}.' };

/**
 * Classe HistoriqueEtat (RG-23) : chaque changement d'état d'un rendez-vous, avec sa date et son auteur.
 * Composition avec RendezVous : l'historique est supprimé avec le rendez-vous.
 * Les lignes sont créées automatiquement par RendezVous.changerEtat().
 */
const historiqueEtatSchema = new mongoose.Schema(
  {
    ancienEtat: { type: String, required: [true, "L'ancien état est obligatoire."], enum: ETATS },
    nouvelEtat: { type: String, required: [true, 'Le nouvel état est obligatoire.'], enum: ETATS },
    dateChangement: { type: Date, default: Date.now },
    rendezVous: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'RendezVous',
      required: [true, 'Le rendez-vous est obligatoire.'],
      validate: doitExister('RendezVous', 'Rendez-vous introuvable.'),
    },
    auteur: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Utilisateur', // secrétaire, médecin ou patient : n'importe quel utilisateur
      validate: doitExister('Utilisateur', 'Auteur introuvable.'),
    },
  },
  { versionKey: false },
);

module.exports = mongoose.model('HistoriqueEtat', historiqueEtatSchema, 'historiques_etats');
