const { Router } = require('express');

const controleur = require('../controllers/dossierMedicalController');

// Routes de la classe DossierMedical, montées sur /api/dossiers-medicaux.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
