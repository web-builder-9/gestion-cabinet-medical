const { Router } = require('express');

const controleur = require('../controllers/patientController');

// Routes de la classe Patient, montées sur /api/patients (voir routes/index.js).
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
