/**
 * Ne garde que les champs autorisés d'un objet (req.body ou req.query).
 * Un client ne peut donc pas modifier lui-même _id, role, etat… en les ajoutant au JSON.
 *
 *   choisir({ libelle: 'Pédiatrie', _id: 'x' }, ['libelle', 'description'])  →  { libelle: 'Pédiatrie' }
 */
function choisir(source, champs) {
  const resultat = {};

  for (const champ of champs) {
    if (source?.[champ] !== undefined) {
      resultat[champ] = source[champ];
    }
  }

  return resultat;
}

module.exports = choisir;
