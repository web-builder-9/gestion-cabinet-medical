const { Router } = require('express');

const controleur = require('../controllers/parametresCabinetController');

// Routes de la classe ParametresCabinet, montées sur /api/parametres-cabinet.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
