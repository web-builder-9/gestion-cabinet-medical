const express = require('express');
const morgan = require('morgan');

const routes = require('./routes');
const { routeIntrouvable, gestionErreurs } = require('./middlewares/erreurs');

const app = express();

// Middlewares exécutés pour chaque requête, dans l'ordre où ils sont déclarés.
app.use(express.json()); // transforme le corps JSON envoyé par Postman en objet JavaScript : req.body

if (process.env.NODE_ENV !== 'test') {
  app.use(morgan('dev')); // affiche chaque requête dans le terminal : « GET /api/patients 200 35 ms »
}

// Toutes les ressources de l'API sont sous le préfixe /api.
app.use('/api', routes);

// Déclarés en dernier : aucune route n'a répondu (404), ou une erreur a été levée.
app.use(routeIntrouvable);
app.use(gestionErreurs);

module.exports = app;
