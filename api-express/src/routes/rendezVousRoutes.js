const { Router } = require('express');

const controleur = require('../controllers/rendezVousController');

// Routes de la classe RendezVous, montées sur /api/rendez-vous.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

// Méthode changerEtat(e) : PATCH modifie une partie de la ressource, ici son état.
router.patch('/:id/etat', controleur.changerEtat);

module.exports = router;
