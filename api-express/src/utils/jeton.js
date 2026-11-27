const jwt = require('jsonwebtoken');

/**
 * Jeton JWT (JSON Web Token) donné à la connexion. Le client le renvoie ensuite dans l'en-tête
 * « Authorization: Bearer <jeton> » de chaque requête protégée.
 *
 * Le jeton contient l'identifiant et le rôle de l'utilisateur, signés avec la clé JWT_SECRET :
 * si quelqu'un modifie son contenu, la signature ne correspond plus et le jeton est refusé.
 * Il n'est pas chiffré : on n'y met jamais de mot de passe ni de donnée médicale.
 */
const CLE_DE_DEVELOPPEMENT = 'cle-de-developpement-a-remplacer';

// La clé vient de .env ; sans elle, une clé de développement est utilisée, sauf en production.
function cleSecrete() {
  if (process.env.JWT_SECRET) {
    return process.env.JWT_SECRET;
  }
  if (process.env.NODE_ENV === 'production') {
    throw new Error('JWT_SECRET manquant : obligatoire en production.');
  }
  return CLE_DE_DEVELOPPEMENT;
}

function creerJeton(utilisateur) {
  return jwt.sign(
    { sub: utilisateur.id, role: utilisateur.role }, // « sub » (subject) : à qui appartient le jeton
    cleSecrete(),
    { algorithm: 'HS256', expiresIn: process.env.JWT_DUREE || '2h' },
  );
}

// Renvoie le contenu du jeton, ou lève une erreur s'il est faux, modifié ou expiré.
function verifierJeton(jeton) {
  return jwt.verify(jeton, cleSecrete(), { algorithms: ['HS256'] });
}

module.exports = { creerJeton, verifierJeton, cleSecrete };
