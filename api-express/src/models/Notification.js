const mongoose = require('mongoose');

const { TypeNotification } = require('./enums');
const { doitExister } = require('./validateurs');

/**
 * Classe Notification : e-mail envoyé au patient au sujet d'un rendez-vous
 * (confirmation, modification, annulation, rappel). Un rendez-vous a 0..* notifications.
 */
const notificationSchema = new mongoose.Schema(
  {
    type: {
      type: String,
      required: [true, 'Le type est obligatoire.'],
      enum: { values: Object.values(TypeNotification), message: 'Type de notification inconnu : {VALUE}.' },
    },
    dateEnvoi: { type: Date },
    envoyee: { type: Boolean, default: false },
    rendezVous: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'RendezVous',
      required: [true, 'Le rendez-vous est obligatoire.'],
      validate: doitExister('RendezVous', 'Rendez-vous introuvable.'),
    },
  },
  { versionKey: false },
);

module.exports = mongoose.model('Notification', notificationSchema, 'notifications');
