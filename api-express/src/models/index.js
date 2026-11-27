/**
 * Les 12 classes du diagramme de classes, chargées en une fois :
 *   const { Patient, RendezVous } = require('../models');
 * Utilisateur est chargé avant Patient et Medecin, qui en héritent.
 */
module.exports = {
  Utilisateur: require('./Utilisateur'),
  Patient: require('./Patient'),
  Medecin: require('./Medecin'),
  Specialite: require('./Specialite'),
  DossierMedical: require('./DossierMedical'),
  RendezVous: require('./RendezVous'),
  Indisponibilite: require('./Indisponibilite'),
  Disponibilite: require('./Disponibilite'),
  HistoriqueEtat: require('./HistoriqueEtat'),
  Consultation: require('./Consultation'),
  Notification: require('./Notification'),
  ParametresCabinet: require('./ParametresCabinet'),
};
