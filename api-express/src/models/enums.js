/**
 * Énumérations du diagramme de classes. Les valeurs sont stockées en texte dans MongoDB,
 * et chaque schéma n'accepte que ces valeurs (option « enum » de Mongoose).
 */

const Role = Object.freeze({
  PATIENT: 'PATIENT',
  MEDECIN: 'MEDECIN',
  SECRETAIRE: 'SECRETAIRE',
  ADMINISTRATEUR: 'ADMINISTRATEUR',
});

const EtatRendezVous = Object.freeze({
  EN_ATTENTE: 'EN_ATTENTE',
  CONFIRME: 'CONFIRME',
  ANNULE: 'ANNULE',
  TERMINE: 'TERMINE',
  ABSENT: 'ABSENT',
});

const TypeNotification = Object.freeze({
  CONFIRMATION: 'CONFIRMATION',
  MODIFICATION: 'MODIFICATION',
  ANNULATION: 'ANNULATION',
  RAPPEL: 'RAPPEL',
});

const JourSemaine = Object.freeze({
  LUNDI: 'LUNDI',
  MARDI: 'MARDI',
  MERCREDI: 'MERCREDI',
  JEUDI: 'JEUDI',
  VENDREDI: 'VENDREDI',
  SAMEDI: 'SAMEDI',
});

const Genre = Object.freeze({
  M: 'M',
  F: 'F',
});

/**
 * RG-17 (diagramme d'états) : états vers lesquels un rendez-vous peut passer.
 * Annulé, Terminé et Absent sont des états finaux. Même règle que l'application Laravel.
 */
const TRANSITIONS = Object.freeze({
  EN_ATTENTE: ['CONFIRME', 'ANNULE'],
  CONFIRME: ['EN_ATTENTE', 'ANNULE', 'TERMINE', 'ABSENT'],
  ANNULE: [],
  TERMINE: [],
  ABSENT: [],
});

/**
 * RG-12 : seuls les rendez-vous « en attente » ou « confirmés » occupent un créneau.
 */
const ETATS_ACTIFS = Object.freeze([EtatRendezVous.EN_ATTENTE, EtatRendezVous.CONFIRME]);

module.exports = { Role, EtatRendezVous, TypeNotification, JourSemaine, Genre, TRANSITIONS, ETATS_ACTIFS };
