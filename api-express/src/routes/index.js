const { Router } = require('express');

/**
 * Regroupe les routes des 12 classes sous /api (préfixe ajouté dans app.js).
 * Les URL sont en français et au pluriel : /api/rendez-vous, /api/dossiers-medicaux…
 */
const router = Router();

const RESSOURCES = {
  utilisateurs: require('./utilisateurRoutes'),
  patients: require('./patientRoutes'),
  medecins: require('./medecinRoutes'),
  specialites: require('./specialiteRoutes'),
  'dossiers-medicaux': require('./dossierMedicalRoutes'),
  'rendez-vous': require('./rendezVousRoutes'),
  indisponibilites: require('./indisponibiliteRoutes'),
  disponibilites: require('./disponibiliteRoutes'),
  'historiques-etats': require('./historiqueEtatRoutes'),
  consultations: require('./consultationRoutes'),
  notifications: require('./notificationRoutes'),
  'parametres-cabinet': require('./parametresCabinetRoutes'),
};

// GET /api : page d'accueil de l'API, utile pour vérifier que le serveur répond.
router.get('/', (req, res) => {
  res.json({
    message: 'API du cabinet médical (Express + MongoDB)',
    ressources: Object.keys(RESSOURCES).map((nom) => `/api/${nom}`),
  });
});

for (const [nom, routes] of Object.entries(RESSOURCES)) {
  router.use(`/${nom}`, routes);
}

module.exports = router;
