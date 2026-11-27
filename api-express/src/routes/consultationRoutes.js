const { Router } = require('express');

const controleur = require('../controllers/consultationController');

// Routes de la classe Consultation, montées sur /api/consultations.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

// Méthode genererOrdonnancePDF() : renvoie un fichier PDF au lieu de JSON.
router.get('/:id/ordonnance', controleur.ordonnance);

module.exports = router;
