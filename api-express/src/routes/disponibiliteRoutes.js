const { Router } = require('express');

const controleur = require('../controllers/disponibiliteController');

// Routes de la classe Disponibilite, montées sur /api/disponibilites.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
