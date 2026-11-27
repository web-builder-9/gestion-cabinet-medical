// Point d'entrée : charge le fichier .env, se connecte à MongoDB, puis démarre le serveur HTTP.
require('dotenv').config({ quiet: true });

const app = require('./src/app');
const { connecterBase } = require('./src/config/db');

const PORT = process.env.PORT || 3000;

connecterBase(process.env.MONGODB_URI)
  .then(() => {
    app.listen(PORT, () => {
      console.log(`API démarrée : http://localhost:${PORT}/api`);
    });
  })
  .catch((erreur) => {
    console.error('Connexion à MongoDB impossible :', erreur.message);
    process.exit(1);
  });
