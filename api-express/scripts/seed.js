/**
 * Données de démonstration, entièrement fictives : npm run seed
 *
 * ATTENTION : vide d'abord toutes les collections de la base indiquée par MONGODB_URI,
 * puis la remplit avec les mêmes comptes que l'application Laravel (mot de passe « password »).
 * Les dates sont calculées à partir d'aujourd'hui : la démo reste à jour.
 */
require('dotenv').config({ quiet: true });

const mongoose = require('mongoose');

const { connecterBase } = require('../src/config/db');
const {
  Utilisateur, Patient, Medecin, Specialite, DossierMedical, RendezVous,
  Indisponibilite, Disponibilite, HistoriqueEtat, Consultation, Notification, ParametresCabinet,
} = require('../src/models');
const { Role, EtatRendezVous, JourSemaine, Genre, TypeNotification } = require('../src/models/enums');

const MOT_DE_PASSE = 'password';

/**
 * Date « dans N jours à HH:MM », heure de Djerba (UTC+1 toute l'année). Un dimanche est remplacé
 * par le jour ouvré voisin : le cabinet est fermé le dimanche.
 */
function jourOuvre(decalage, heure) {
  const date = new Date();
  date.setUTCDate(date.getUTCDate() + decalage);
  if (date.getUTCDay() === 0) {
    date.setUTCDate(date.getUTCDate() + (decalage < 0 ? -1 : 1));
  }
  return new Date(`${date.toISOString().slice(0, 10)}T${heure}:00+01:00`);
}

async function viderLaBase() {
  // Attend la création des index uniques (e-mail, CIN…) avant d'insérer quoi que ce soit.
  await Promise.all([Utilisateur, Patient, Medecin, Specialite, DossierMedical, RendezVous, Indisponibilite,
    Disponibilite, HistoriqueEtat, Consultation, Notification, ParametresCabinet].map((modele) => modele.init()));

  // Utilisateur contient aussi les patients et les médecins (même collection).
  for (const modele of [Utilisateur, Specialite, DossierMedical, RendezVous, Indisponibilite, Disponibilite,
    HistoriqueEtat, Consultation, Notification, ParametresCabinet]) {
    await modele.deleteMany({});
  }
}

async function main() {
  await connecterBase(process.env.MONGODB_URI);
  console.log(`Base « ${mongoose.connection.name} » : suppression des données existantes…`);
  await viderLaBase();

  // ParametresCabinet : valeurs par défaut du schéma (08:00-18:00, créneaux de 30 min…).
  await ParametresCabinet.create({});

  // Specialite (Dermatologie n'a pas de médecin : on peut la supprimer pendant la démo).
  const [generale, pediatrie, cardiologie] = await Specialite.create([
    { libelle: 'Médecine générale', description: 'Suivi des adultes et des enfants, certificats, maladies chroniques.' },
    { libelle: 'Pédiatrie', description: 'Santé des enfants de 0 à 16 ans.' },
    { libelle: 'Cardiologie', description: 'Cœur et vaisseaux : hypertension, bilans cardiaques.' },
    { libelle: 'Dermatologie', description: 'Peau, cheveux et ongles.' },
  ]);

  // Utilisateur : personnel (une secrétaire désactivée pour montrer RG-02).
  const personnel = [
    { nom: 'Ferjani', prenom: 'Ridha', email: 'admin@cabinet.test', telephone: '20 100 200', role: Role.ADMINISTRATEUR },
    { nom: 'Jlassi', prenom: 'Nadia', email: 'secretaire@cabinet.test', telephone: '20 100 300', role: Role.SECRETAIRE },
    { nom: 'Mabrouk', prenom: 'Hela', email: 'h.mabrouk@cabinet.test', telephone: '20 100 400', role: Role.SECRETAIRE, actif: false },
  ];
  // create() un par un : le hook « pre save » hache chaque mot de passe.
  const comptes = [];
  for (const compte of personnel) {
    comptes.push(await Utilisateur.create({ ...compte, motDePasse: MOT_DE_PASSE }));
  }
  const secretaire = comptes[1];

  // Medecin
  const salma = await Medecin.create({
    nom: 'Ben Ali', prenom: 'Salma', email: 'medecin@cabinet.test', telephone: '20 200 100', motDePasse: MOT_DE_PASSE,
    specialite: generale._id, presentation: 'Suivi des adultes et des enfants, certificats, maladies chroniques.',
  });
  const mehdi = await Medecin.create({
    nom: 'Saidi', prenom: 'Mehdi', email: 'm.saidi@cabinet.test', telephone: '20 200 200', motDePasse: MOT_DE_PASSE,
    specialite: pediatrie._id, presentation: 'Enfants de 0 à 16 ans, vaccinations, suivi de croissance.',
  });
  const leila = await Medecin.create({
    nom: 'Ammar', prenom: 'Leila', email: 'l.ammar@cabinet.test', telephone: '20 200 300', motDePasse: MOT_DE_PASSE,
    specialite: cardiologie._id, presentation: 'Hypertension, bilans cardiaques, électrocardiogramme.',
  });

  // Patient
  const amira = await Patient.create({
    nom: 'Ben Salah', prenom: 'Amira', email: 'patient@cabinet.test', telephone: '98 451 220', motDePasse: MOT_DE_PASSE,
    cin: '08412337', dateNaissance: '1992-06-14', genre: Genre.F,
    adresse: 'Avenue Habib Bourguiba, Midoun, Djerba', consentementDonnees: true,
  });
  const karim = await Patient.create({
    nom: 'Haddad', prenom: 'Karim', email: 'k.haddad@exemple.tn', telephone: '97 310 482', motDePasse: MOT_DE_PASSE,
    cin: '07519264', dateNaissance: '1968-02-03', genre: Genre.M, adresse: 'Houmt Souk, Djerba', consentementDonnees: true,
  });
  const yasmine = await Patient.create({
    nom: 'Trabelsi', prenom: 'Yasmine', email: 'y.trabelsi@exemple.tn', telephone: '55 208 731', motDePasse: MOT_DE_PASSE,
    cin: '11846025', dateNaissance: '2001-09-22', genre: Genre.F, adresse: 'Ajim, Djerba',
  });
  const omar = await Patient.create({
    nom: 'Gharbi', prenom: 'Omar', email: 'o.gharbi@exemple.tn', telephone: '29 664 105', motDePasse: MOT_DE_PASSE,
    cin: '14273958', dateNaissance: '2017-04-11', genre: Genre.M, adresse: 'Midoun, Djerba', consentementDonnees: true,
  });

  // DossierMedical (Yasmine et Omar n'en ont pas encore : multiplicité 0..1).
  await DossierMedical.create([
    { patient: amira._id, groupeSanguin: 'O+', allergies: 'Pénicilline', antecedents: "Asthme léger depuis l'enfance" },
    { patient: karim._id, groupeSanguin: 'A+', allergies: 'Aucune connue', antecedents: 'Hypertension artérielle (2019)' },
  ]);

  // Disponibilite : plages hebdomadaires.
  const plages = [
    ...[JourSemaine.LUNDI, JourSemaine.MARDI, JourSemaine.MERCREDI, JourSemaine.JEUDI, JourSemaine.VENDREDI]
      .map((jour) => ({ medecin: salma._id, jour, heureDebut: '08:30', heureFin: '12:30', dureeCreneau: 30 })),
    ...[JourSemaine.MARDI, JourSemaine.JEUDI]
      .map((jour) => ({ medecin: salma._id, jour, heureDebut: '14:00', heureFin: '17:00', dureeCreneau: 30 })),
    ...[JourSemaine.LUNDI, JourSemaine.MERCREDI, JourSemaine.SAMEDI]
      .map((jour) => ({ medecin: mehdi._id, jour, heureDebut: '09:00', heureFin: '13:00', dureeCreneau: 20 })),
    ...[JourSemaine.MARDI, JourSemaine.JEUDI]
      .map((jour) => ({ medecin: leila._id, jour, heureDebut: '08:00', heureFin: '12:00', dureeCreneau: 45 })),
  ];
  await Disponibilite.create(plages);

  // Indisponibilite
  await Indisponibilite.create({
    medecin: leila._id, debut: jourOuvre(10, '00:00'), fin: jourOuvre(14, '23:59'), motif: 'Congrès de cardiologie',
  });

  // RendezVous, avec leurs changements d'état (HistoriqueEtat créé par changerEtat).
  const passe = await RendezVous.create({
    patient: amira._id, medecin: salma._id, debut: jourOuvre(-2, '09:00'), fin: jourOuvre(-2, '09:30'), motif: 'Toux persistante',
  });
  await passe.changerEtat(EtatRendezVous.CONFIRME, secretaire._id);
  await passe.changerEtat(EtatRendezVous.TERMINE, salma._id);

  const confirme = await RendezVous.create({
    patient: karim._id, medecin: leila._id, debut: jourOuvre(1, '10:00'), fin: jourOuvre(1, '10:45'), motif: 'Bilan tension',
  });
  await confirme.changerEtat(EtatRendezVous.CONFIRME, secretaire._id);

  await RendezVous.create({
    patient: yasmine._id, medecin: salma._id, debut: jourOuvre(2, '11:00'), fin: jourOuvre(2, '11:30'), motif: 'Certificat médical',
  });

  const annule = await RendezVous.create({
    patient: omar._id, medecin: mehdi._id, debut: jourOuvre(3, '09:00'), fin: jourOuvre(3, '09:20'), motif: 'Vaccination',
  });
  await annule.changerEtat(EtatRendezVous.ANNULE, secretaire._id);

  // Consultation du rendez-vous terminé : son ordonnance PDF est disponible.
  await Consultation.create({
    rendezVous: passe._id,
    date: passe.debut,
    motif: 'Toux persistante depuis dix jours',
    observations: 'Auscultation : quelques sibilants. Pas de fièvre.',
    diagnostic: 'Bronchite aiguë',
    traitement: 'Amoxicilline 1 g : 1 comprimé matin et soir pendant 7 jours\nParacétamol 1 g : si fièvre, 3 fois par jour au maximum\nSirop antitussif : 1 cuillère le soir pendant 5 jours',
  });

  // Notification
  await Notification.create([
    { rendezVous: passe._id, type: TypeNotification.CONFIRMATION, envoyee: true, dateEnvoi: jourOuvre(-3, '15:00') },
    { rendezVous: confirme._id, type: TypeNotification.CONFIRMATION, envoyee: true, dateEnvoi: new Date() },
    { rendezVous: confirme._id, type: TypeNotification.RAPPEL, envoyee: false },
    { rendezVous: annule._id, type: TypeNotification.ANNULATION, envoyee: true, dateEnvoi: new Date() },
  ]);

  console.log('Données de démonstration créées :');
  for (const [nom, modele] of Object.entries({
    utilisateurs: Utilisateur, specialites: Specialite, dossiers: DossierMedical, disponibilites: Disponibilite,
    indisponibilites: Indisponibilite, rendezVous: RendezVous, historiques: HistoriqueEtat,
    consultations: Consultation, notifications: Notification, parametres: ParametresCabinet,
  })) {
    console.log(`  ${nom.padEnd(17)} ${await modele.countDocuments()}`);
  }
  console.log(`Comptes : admin@cabinet.test, secretaire@cabinet.test, medecin@cabinet.test, patient@cabinet.test (mot de passe « ${MOT_DE_PASSE} »)`);
}

main()
  .then(() => mongoose.disconnect())
  .catch(async (erreur) => {
    console.error('Échec du remplissage :', erreur.message);
    await mongoose.disconnect();
    process.exit(1);
  });
