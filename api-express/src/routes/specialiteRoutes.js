const { Router } = require('express');

const controleur = require('../controllers/specialiteController');

// Routes de la classe Specialite, montées sur /api/specialites.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
