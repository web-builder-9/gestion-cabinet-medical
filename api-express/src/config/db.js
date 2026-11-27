const mongoose = require('mongoose');

/**
 * Connexion à MongoDB avec Mongoose : Atlas (cloud) en démonstration,
 * une base locale ou le service de la CI pour les tests.
 */
async function connecterBase(uri) {
  if (!uri) {
    throw new Error('MONGODB_URI manquant : copier .env.example en .env et y coller la chaîne de connexion Atlas.');
  }

  // Abandonne après 10 s au lieu de 30 s si Atlas ne répond pas (IP non autorisée, réseau bloqué…).
  await mongoose.connect(uri, { serverSelectionTimeoutMS: 10000 });

  console.log(`Connecté à MongoDB, base « ${mongoose.connection.name} »`);
}

module.exports = { connecterBase };
