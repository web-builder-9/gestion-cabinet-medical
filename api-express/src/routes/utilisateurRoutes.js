const { Router } = require('express');

const controleur = require('../controllers/utilisateurController');

// Routes de la classe Utilisateur, montées sur /api/utilisateurs.
const router = Router();

// Méthode seConnecter() : route fixe déclarée avant les routes avec « :id ».
router.post('/connexion', controleur.connexion);

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
