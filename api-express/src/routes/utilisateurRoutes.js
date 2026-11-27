const { Router } = require('express');

const controleur = require('../controllers/utilisateurController');
const { exigerConnexion } = require('../middlewares/authentification');

// Routes de la classe Utilisateur, montées sur /api/utilisateurs.
const router = Router();

// Routes fixes déclarées avant les routes avec « :id » (sinon « moi » serait pris pour un identifiant).
// Méthode seConnecter() : renvoie un jeton JWT.
router.post('/connexion', controleur.connexion);
// Route protégée : exigerConnexion vérifie le jeton avant d'appeler le contrôleur.
router.get('/moi', exigerConnexion, controleur.moi);

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
