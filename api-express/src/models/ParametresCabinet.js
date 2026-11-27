const mongoose = require('mongoose');

const { FORMAT_HEURE } = require('./validateurs');

/**
 * Classe ParametresCabinet : réglages généraux du cabinet. Il n'en existe qu'un seul document
 * (voir parametresCabinetController.creer).
 */
const parametresCabinetSchema = new mongoose.Schema(
  {
    heureOuverture: {
      type: String,
      default: '08:00',
      match: [FORMAT_HEURE, "L'heure d'ouverture doit être au format HH:MM."],
    },
    heureFermeture: {
      type: String,
      default: '18:00',
      match: [FORMAT_HEURE, "L'heure de fermeture doit être au format HH:MM."],
      validate: {
        validator: function (heureFermeture) {
          return heureFermeture > this.heureOuverture;
        },
        message: "L'heure de fermeture doit être après l'heure d'ouverture.",
      },
    },
    dureeCreneauDefaut: {
      type: Number,
      default: 30,
      min: [5, 'Un créneau dure au moins 5 minutes.'],
      max: [240, 'Un créneau dure au plus 240 minutes.'],
      validate: { validator: Number.isInteger, message: 'La durée est un nombre entier de minutes.' },
    },
    delaiAnnulationHeures: {
      type: Number,
      default: 24,
      min: [0, "Le délai d'annulation ne peut pas être négatif."],
    },
    delaiReservationJours: {
      type: Number,
      default: 60,
      min: [1, 'Le délai de réservation est au moins de 1 jour.'],
    },
  },
  { versionKey: false },
);

module.exports = mongoose.model('ParametresCabinet', parametresCabinetSchema, 'parametres_cabinet');
