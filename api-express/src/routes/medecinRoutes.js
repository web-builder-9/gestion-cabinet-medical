const { Router } = require('express');

const controleur = require('../controllers/medecinController');

// Routes de la classe Medecin, montées sur /api/medecins.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
