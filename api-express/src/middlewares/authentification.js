const { Utilisateur } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const { verifierJeton } = require('../utils/jeton');

/**
 * Middleware qui protège une route : la requête doit porter un jeton valide, d'un compte actif.
 *   router.get('/moi', exigerConnexion, controleur.moi);
 * Il s'exécute avant le contrôleur ; le compte connecté est ensuite disponible dans req.utilisateur.
 */
async function exigerConnexion(req, res, next) {
  // En-tête attendu : « Authorization: Bearer eyJhbGciOi… »
  const [type, jeton] = (req.get('Authorization') ?? '').split(' ');
  if (type !== 'Bearer' || !jeton) {
    throw new ErreurApi(401, "Connexion requise : envoyez l'en-tête « Authorization: Bearer <jeton> ».");
  }

  let contenu;
  try {
    contenu = verifierJeton(jeton);
  } catch (erreur) {
    throw new ErreurApi(401, erreur.name === 'TokenExpiredError' ? 'Jeton expiré : reconnectez-vous.' : 'Jeton invalide.');
  }

  // Le compte a pu être supprimé ou désactivé depuis la connexion (RG-02).
  const utilisateur = await Utilisateur.findById(contenu.sub);
  if (!utilisateur || !utilisateur.actif) {
    throw new ErreurApi(401, 'Compte introuvable ou désactivé : reconnectez-vous.');
  }

  req.utilisateur = utilisateur;
  next();
}

module.exports = { exigerConnexion };
