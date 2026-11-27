const { Notification } = require('../models');
const ErreurApi = require('../utils/ErreurApi');
const choisir = require('../utils/choisir');

/**
 * Classe Notification (un rendez-vous → 0..* notifications).
 */
const CHAMPS_CREATION = ['type', 'dateEnvoi', 'envoyee', 'rendezVous'];
const CHAMPS_MODIFICATION = ['type', 'dateEnvoi', 'envoyee'];

const AVEC_RENDEZ_VOUS = { path: 'rendezVous', select: 'debut fin etat' };

async function trouver(id) {
  const notification = await Notification.findById(id);
  if (!notification) {
    throw new ErreurApi(404, 'Notification introuvable.');
  }
  return notification;
}

// GET /api/notifications           filtres facultatifs : ?rendezVous=<id>&envoyee=false
async function lister(req, res) {
  const notifications = await Notification.find(choisir(req.query, ['rendezVous', 'envoyee', 'type']))
    .sort({ dateEnvoi: -1 })
    .populate(AVEC_RENDEZ_VOUS);
  res.json(notifications);
}

// GET /api/notifications/:id
async function afficher(req, res) {
  const notification = await trouver(req.params.id);
  await notification.populate(AVEC_RENDEZ_VOUS);
  res.json(notification);
}

// POST /api/notifications          { type, rendezVous, envoyee, dateEnvoi }
async function creer(req, res) {
  const notification = await Notification.create(choisir(req.body, CHAMPS_CREATION));
  res.status(201).json(notification);
}

// PUT /api/notifications/:id       par exemple { "envoyee": true, "dateEnvoi": "2026-11-29T10:00:00+01:00" }
async function modifier(req, res) {
  const notification = await trouver(req.params.id);

  notification.set(choisir(req.body, CHAMPS_MODIFICATION));
  await notification.save();

  res.json(notification);
}

// DELETE /api/notifications/:id
async function supprimer(req, res) {
  const notification = await trouver(req.params.id);

  await notification.deleteOne();
  res.json({ message: 'Notification supprimée.' });
}

module.exports = { lister, afficher, creer, modifier, supprimer };
