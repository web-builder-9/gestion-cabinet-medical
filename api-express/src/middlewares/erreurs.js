const mongoose = require('mongoose');

/**
 * Aucune route ne correspond à la requête : 404.
 */
function routeIntrouvable(req, res) {
  res.status(404).json({ message: `Route introuvable : ${req.method} ${req.originalUrl}` });
}

/**
 * Gestionnaire d'erreurs central : Express le reconnaît à ses 4 paramètres (err, req, res, next).
 * Chaque type d'erreur est traduit en un code HTTP et un message en français.
 * « next » n'est pas utilisé, mais il doit être déclaré pour qu'Express reconnaisse ce rôle.
 */
function gestionErreurs(err, req, res, next) { // eslint-disable-line no-unused-vars
  // 400 : le JSON envoyé est mal écrit (virgule en trop, guillemets manquants…).
  if (err.type === 'entity.parse.failed') {
    return res.status(400).json({ message: "Le corps de la requête n'est pas un JSON valide." });
  }

  // 400 : une règle du schéma Mongoose n'est pas respectée (champ obligatoire, format, énumération…).
  if (err instanceof mongoose.Error.ValidationError) {
    const erreurs = {};
    for (const [champ, detail] of Object.entries(err.errors)) {
      erreurs[champ] = detail.name === 'CastError' ? `Valeur invalide : ${detail.value}` : detail.message;
    }
    return res.status(400).json({ message: 'Données invalides.', erreurs });
  }

  // 400 : identifiant mal formé, par exemple GET /api/patients/123.
  if (err instanceof mongoose.Error.CastError) {
    return res.status(400).json({ message: `Identifiant ou valeur invalide pour « ${err.path} » : ${err.value}` });
  }

  // 409 : valeur déjà utilisée dans un champ unique (index unique de MongoDB, code 11000).
  if (err.code === 11000) {
    const champ = Object.keys(err.keyValue ?? err.keyPattern ?? {})[0];
    return res.status(409).json({
      message: `Cette valeur est déjà utilisée : ${champ}.`,
      erreurs: { [champ]: `Déjà utilisé : ${err.keyValue?.[champ]}` },
    });
  }

  // Erreurs « métier » levées avec ErreurApi : 401, 404, 409…
  if (err.statut) {
    return res.status(err.statut).json({ message: err.message });
  }

  // Erreur imprévue : détail dans le terminal, message générique pour le client.
  console.error(err);
  res.status(500).json({ message: 'Erreur interne du serveur.' });
}

module.exports = { routeIntrouvable, gestionErreurs };
