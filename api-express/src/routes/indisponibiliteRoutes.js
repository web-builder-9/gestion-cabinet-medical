const { Router } = require('express');

const controleur = require('../controllers/indisponibiliteController');

// Routes de la classe Indisponibilite, montées sur /api/indisponibilites.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
