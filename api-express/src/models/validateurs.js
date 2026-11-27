const mongoose = require('mongoose');

/**
 * MongoDB ne vérifie pas les références entre collections (pas de clé étrangère comme en SQL).
 * Ce validateur vérifie qu'un identifiant désigne bien un document existant :
 *   patient: { type: ObjectId, ref: 'Patient', validate: doitExister('Patient', 'Patient introuvable.') }
 */
function doitExister(nomModele, message) {
  return {
    validator: async (id) => (await mongoose.model(nomModele).exists({ _id: id })) !== null,
    message,
  };
}

/** Heure au format « HH:MM » sur 24 heures : 08:30, 17:00… */
const FORMAT_HEURE = /^([01]\d|2[0-3]):[0-5]\d$/;

/**
 * Téléphone tunisien : 8 chiffres, toujours enregistré sous la forme « 22 123 456 »
 * (« 22123456 » et « 22-123-456 » sont acceptés et mis en forme).
 */
function formaterTelephone(valeur) {
  const chiffres = String(valeur ?? '').replace(/\D/g, '');

  if (chiffres.length !== 8) {
    return valeur; // laissé tel quel : la validation « match » le refusera
  }

  return `${chiffres.slice(0, 2)} ${chiffres.slice(2, 5)} ${chiffres.slice(5)}`;
}

module.exports = { doitExister, FORMAT_HEURE, formaterTelephone };
