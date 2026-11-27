/**
 * Erreur « métier » avec un code HTTP, levée par les contrôleurs et les modèles :
 *   throw new ErreurApi(404, 'Patient introuvable.');
 * Express 5 transmet automatiquement les erreurs des fonctions async au gestionnaire d'erreurs
 * (src/middlewares/erreurs.js), qui renvoie { message } avec ce code.
 */
class ErreurApi extends Error {
  constructor(statut, message) {
    super(message);
    this.statut = statut;
  }
}

module.exports = ErreurApi;
