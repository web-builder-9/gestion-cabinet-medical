/**
 * Outils communs aux tests de l'API (node:test + supertest).
 *
 * Les tests utilisent une base à part, MONGODB_URI_TEST, vidée avant chaque test : jamais la base de démo.
 * Sans MONGODB_URI_TEST, les tests qui ont besoin de MongoDB sont ignorés (la CI GitHub en fournit une).
 */
process.env.NODE_ENV = 'test';
require('dotenv').config({ quiet: true });

const mongoose = require('mongoose');
const request = require('supertest');

const app = require('../src/app');
const modeles = require('../src/models');

const URI = process.env.MONGODB_URI_TEST;

// Valeur de l'option « skip » de describe() : false (on lance) ou la raison de l'ignorer.
const sansBase = URI ? false : 'MONGODB_URI_TEST non défini : tests avec MongoDB ignorés';

async function connecter() {
  await mongoose.connect(URI, { serverSelectionTimeoutMS: 5000 });

  if (!mongoose.connection.name.includes('test')) {
    throw new Error(`Refus de vider « ${mongoose.connection.name} » : le nom de la base de test doit contenir « test ».`);
  }

  // Attend la création des index uniques avant le premier test.
  await Promise.all(Object.values(modeles).map((modele) => modele.init()));
}

async function viderBase() {
  for (const modele of Object.values(modeles)) {
    await modele.deleteMany({});
  }
}

async function deconnecter() {
  await mongoose.disconnect();
}

// Requête HTTP vers l'application, sans démarrer de serveur : api().get('/api/patients')
const api = () => request(app);

// Données valides et uniques pour préparer les tests.
let compteur = 0;
function suivant() {
  compteur += 1;
  return compteur;
}

const fabriquer = {
  specialite: (donnees = {}) => modeles.Specialite.create({ libelle: `Spécialité ${suivant()}`, ...donnees }),

  patient: (donnees = {}) => {
    const n = suivant();
    return modeles.Patient.create({
      nom: 'Testeur', prenom: `Patient ${n}`, email: `patient${n}@test.tn`, motDePasse: 'password',
      telephone: String(50000000 + n), cin: String(10000000 + n), dateNaissance: '1990-05-01', genre: 'F',
      ...donnees,
    });
  },

  medecin: async (donnees = {}) => {
    const n = suivant();
    const specialite = donnees.specialite ?? (await fabriquer.specialite())._id;
    return modeles.Medecin.create({
      nom: 'Docteur', prenom: `Médecin ${n}`, email: `medecin${n}@test.tn`, motDePasse: 'password',
      telephone: String(70000000 + n), specialite, ...donnees,
    });
  },

  // new + save() : Utilisateur.create() chercherait une sous-classe « SECRETAIRE » (voir models/Utilisateur.js).
  secretaire: (donnees = {}) => {
    const n = suivant();
    return new modeles.Utilisateur({
      nom: 'Secrétaire', prenom: `Compte ${n}`, email: `secretaire${n}@test.tn`, motDePasse: 'password',
      telephone: String(90000000 + n), role: 'SECRETAIRE', ...donnees,
    }).save();
  },

  rendezVous: async (donnees = {}) => {
    const patient = donnees.patient ?? (await fabriquer.patient())._id;
    const medecin = donnees.medecin ?? (await fabriquer.medecin())._id;
    return modeles.RendezVous.create({
      debut: '2026-12-07T09:00:00+01:00', fin: '2026-12-07T09:30:00+01:00', motif: 'Contrôle',
      ...donnees, patient, medecin,
    });
  },
};

// Identifiant valide qui n'existe dans aucune collection.
const idInconnu = () => new mongoose.Types.ObjectId().toString();

module.exports = { api, connecter, viderBase, deconnecter, sansBase, fabriquer, idInconnu, modeles };
