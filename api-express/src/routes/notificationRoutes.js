const { Router } = require('express');

const controleur = require('../controllers/notificationController');

// Routes de la classe Notification, montées sur /api/notifications.
const router = Router();

router.get('/', controleur.lister);
router.get('/:id', controleur.afficher);
router.post('/', controleur.creer);
router.put('/:id', controleur.modifier);
router.delete('/:id', controleur.supprimer);

module.exports = router;
