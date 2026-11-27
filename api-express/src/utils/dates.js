/**
 * MongoDB enregistre les dates en UTC (« 2026-12-01T08:00:00.000Z » = 9 h à Djerba).
 * Pour les messages et l'ordonnance, on les affiche à l'heure de la Tunisie.
 */
const FUSEAU = 'Africa/Tunis';

function dateHeure(date) {
  return new Date(date).toLocaleString('fr-FR', { timeZone: FUSEAU, dateStyle: 'short', timeStyle: 'short' });
}

function dateSeule(date) {
  return new Date(date).toLocaleDateString('fr-FR', { timeZone: FUSEAU, dateStyle: 'long' });
}

module.exports = { dateHeure, dateSeule };
