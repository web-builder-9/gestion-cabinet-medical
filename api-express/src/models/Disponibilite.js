const mongoose = require('mongoose');

const { JourSemaine } = require('./enums');
const { doitExister, FORMAT_HEURE } = require('./validateurs');

/**
 * Classe Disponibilite : plage de consultation hebdomadaire d'un médecin,
 * par exemple le LUNDI de 08:30 à 12:30, en créneaux de 30 minutes.
 * Composition avec Medecin : supprimée avec le médecin.
 */
const disponibiliteSchema = new mongoose.Schema(
  {
    jour: {
      type: String,
      required: [true, 'Le jour est obligatoire.'],
      enum: { values: Object.values(JourSemaine), message: 'Jour inconnu : {VALUE} (LUNDI à SAMEDI).' },
    },
    heureDebut: {
      type: String,
      required: [true, "L'heure de début est obligatoire."],
      match: [FORMAT_HEURE, "L'heure de début doit être au format HH:MM."],
    },
    heureFin: {
      type: String,
      required: [true, "L'heure de fin est obligatoire."],
      match: [FORMAT_HEURE, "L'heure de fin doit être au format HH:MM."],
      validate: {
        // Au format HH:MM, l'ordre alphabétique est aussi l'ordre des heures : "09:00" < "12:30".
        validator: function (heureFin) {
          return !this.heureDebut || heureFin > this.heureDebut;
        },
        message: "L'heure de fin doit être après l'heure de début.",
      },
    },
    dureeCreneau: {
      type: Number,
      required: [true, 'La durée du créneau est obligatoire.'],
      min: [5, 'Un créneau dure au moins 5 minutes.'],
      max: [240, 'Un créneau dure au plus 240 minutes.'],
      validate: { validator: Number.isInteger, message: 'La durée du créneau est un nombre entier de minutes.' },
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
 * Même règle de chevauchement que les rendez-vous, sur les heures du même jour :
 * un médecin ne peut pas avoir deux plages qui se recouvrent le même jour.
 */
disponibiliteSchema.methods.trouverConflit = function () {
  return this.constructor.findOne({
    _id: { $ne: this._id },
    medecin: this.medecin,
    jour: this.jour,
    heureDebut: { $lt: this.heureFin },
    heureFin: { $gt: this.heureDebut },
  });
};

module.exports = mongoose.model('Disponibilite', disponibiliteSchema, 'disponibilites');
