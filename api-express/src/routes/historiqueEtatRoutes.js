const { Router } = require('express');

const controleur = require('../controllers/historiqueEtatController');

// Routes de la classe HistoriqueEtat, montées sur /api/historiques-etats.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
