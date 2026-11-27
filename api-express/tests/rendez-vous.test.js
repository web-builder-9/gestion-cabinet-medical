/**
 * RendezVous, HistoriqueEtat, Consultation (ordonnance PDF) et Notification.
 */
const { describe, it, before, after, beforeEach } = require('node:test');
const assert = require('node:assert/strict');

const { api, connecter, viderBase, deconnecter, sansBase, fabriquer, idInconnu, modeles } = require('./aide');

const a = (heure) => `2026-12-07T${heure}:00+01:00`; // le 7 décembre 2026 à l'heure de Djerba

describe('Rendez-vous et classes liées', { skip: sansBase }, () => {
  before(connecter);
  after(deconnecter);
  beforeEach(viderBase);

  describe('RendezVous', () => {
    it('crée un rendez-vous en attente puis l\'affiche avec son patient et son médecin', async () => {
      const patient = await fabriquer.patient({ nom: 'Ben Salah' });
      const medecin = await fabriquer.medecin();

      const creation = await api().post('/api/rendez-vous').send({
        patient: patient.id, medecin: medecin.id, debut: a('09:00'), fin: a('09:30'), motif: 'Toux', etat: 'TERMINE',
      });
      assert.equal(creation.status, 201);
      assert.equal(creation.body.etat, 'EN_ATTENTE'); // l'état envoyé est ignoré
      assert.ok(creation.body.creeLe);

      const affichage = await api().get(`/api/rendez-vous/${creation.body._id}`);
      assert.equal(affichage.body.patient.nom, 'Ben Salah');
      assert.ok(affichage.body.medecin.specialite.libelle);

      const liste = await api().get(`/api/rendez-vous?medecin=${medecin.id}&etat=EN_ATTENTE`);
      assert.equal(liste.body.length, 1);
    });

    it('refuse une fin avant le début et un patient inconnu (400)', async () => {
      const medecin = await fabriquer.medecin();

      const reponse = await api().post('/api/rendez-vous').send({
        patient: idInconnu(), medecin: medecin.id, debut: a('10:00'), fin: a('09:00'),
      });
      assert.equal(reponse.status, 400);
      assert.equal(reponse.body.erreurs.fin, 'La fin doit être après le début.');
      assert.equal(reponse.body.erreurs.patient, 'Patient introuvable.');
    });

    it('refuse un créneau qui chevauche un rendez-vous du médecin ou du patient (RG-12)', async () => {
      const existant = await fabriquer.rendezVous({ debut: a('09:00'), fin: a('09:30') });
      const autrePatient = await fabriquer.patient();
      const autreMedecin = await fabriquer.medecin();

      const memeMedecin = await api().post('/api/rendez-vous').send({
        patient: autrePatient.id, medecin: existant.medecin, debut: a('09:15'), fin: a('09:45'),
      });
      assert.equal(memeMedecin.status, 409);
      assert.match(memeMedecin.body.message, /le médecin/);

      const memePatient = await api().post('/api/rendez-vous').send({
        patient: existant.patient, medecin: autreMedecin.id, debut: a('09:00'), fin: a('09:30'),
      });
      assert.equal(memePatient.status, 409);
      assert.match(memePatient.body.message, /le patient/);

      const juste_apres = await api().post('/api/rendez-vous').send({
        patient: autrePatient.id, medecin: existant.medecin, debut: a('09:30'), fin: a('10:00'),
      });
      assert.equal(juste_apres.status, 201);
    });

    it('libère le créneau d\'un rendez-vous annulé', async () => {
      const annule = await fabriquer.rendezVous({ debut: a('09:00'), fin: a('09:30') });
      await annule.changerEtat('ANNULE');

      const reponse = await api().post('/api/rendez-vous').send({
        patient: annule.patient, medecin: annule.medecin, debut: a('09:00'), fin: a('09:30'),
      });
      assert.equal(reponse.status, 201);
    });

    it('déplace un rendez-vous, sauf sur un créneau pris ou s\'il est annulé', async () => {
      const medecin = await fabriquer.medecin();
      const premier = await fabriquer.rendezVous({ medecin: medecin._id, debut: a('09:00'), fin: a('09:30') });
      const second = await fabriquer.rendezVous({ medecin: medecin._id, debut: a('10:00'), fin: a('10:30') });

      const deplacement = await api().put(`/api/rendez-vous/${second.id}`).send({ debut: a('11:00'), fin: a('11:30'), etat: 'ANNULE' });
      assert.equal(deplacement.status, 200);
      assert.equal(deplacement.body.etat, 'EN_ATTENTE');

      const conflit = await api().put(`/api/rendez-vous/${second.id}`).send({ debut: a('09:00'), fin: a('09:30') });
      assert.equal(conflit.status, 409);

      // Modifier uniquement le motif n'entre pas en conflit avec lui-même.
      assert.equal((await api().put(`/api/rendez-vous/${premier.id}`).send({ motif: 'Contrôle annuel' })).status, 200);

      await premier.changerEtat('ANNULE');
      assert.equal((await api().put(`/api/rendez-vous/${premier.id}`).send({ motif: 'X' })).status, 409);
    });
  });

  describe('changerEtat() : PATCH /api/rendez-vous/:id/etat', () => {
    it('confirme un rendez-vous et historise le changement (RG-17, RG-23)', async () => {
      const rendezVous = await fabriquer.rendezVous();
      const secretaire = await fabriquer.secretaire({ nom: 'Jlassi' });

      const reponse = await api().patch(`/api/rendez-vous/${rendezVous.id}/etat`).send({ etat: 'CONFIRME', auteur: secretaire.id });
      assert.equal(reponse.status, 200);
      assert.equal(reponse.body.rendezVous.etat, 'CONFIRME');
      assert.equal(reponse.body.historique.ancienEtat, 'EN_ATTENTE');
      assert.equal(reponse.body.historique.nouvelEtat, 'CONFIRME');

      const historique = await api().get(`/api/historiques-etats?rendezVous=${rendezVous.id}`);
      assert.equal(historique.body.length, 1);
      assert.equal(historique.body[0].auteur.nom, 'Jlassi');
    });

    it('refuse une transition interdite (409) et un état inconnu (400)', async () => {
      const rendezVous = await fabriquer.rendezVous();
      await rendezVous.changerEtat('ANNULE');

      const interdite = await api().patch(`/api/rendez-vous/${rendezVous.id}/etat`).send({ etat: 'CONFIRME' });
      assert.equal(interdite.status, 409);

      const inconnu = await api().patch(`/api/rendez-vous/${rendezVous.id}/etat`).send({ etat: 'REPORTE' });
      assert.equal(inconnu.status, 400);
    });

    it('ne change rien si l\'auteur n\'existe pas (400)', async () => {
      const rendezVous = await fabriquer.rendezVous();

      const reponse = await api().patch(`/api/rendez-vous/${rendezVous.id}/etat`).send({ etat: 'CONFIRME', auteur: idInconnu() });
      assert.equal(reponse.status, 400);

      const enBase = await modeles.RendezVous.findById(rendezVous._id);
      assert.equal(enBase.etat, 'EN_ATTENTE');
      assert.equal(await modeles.HistoriqueEtat.countDocuments(), 0);
    });
  });

  describe('HistoriqueEtat', () => {
    it('crée, modifie et supprime une ligne d\'historique', async () => {
      const rendezVous = await fabriquer.rendezVous();

      const creation = await api().post('/api/historiques-etats').send({ rendezVous: rendezVous.id, ancienEtat: 'EN_ATTENTE', nouvelEtat: 'CONFIRME' });
      assert.equal(creation.status, 201);
      assert.ok(creation.body.dateChangement);

      const modification = await api().put(`/api/historiques-etats/${creation.body._id}`).send({ nouvelEtat: 'ANNULE' });
      assert.equal(modification.body.nouvelEtat, 'ANNULE');

      assert.equal((await api().get(`/api/historiques-etats/${creation.body._id}`)).status, 200);
      assert.equal((await api().delete(`/api/historiques-etats/${creation.body._id}`)).status, 200);
    });
  });

  describe('Consultation', () => {
    it('crée une consultation, une seule par rendez-vous, et génère l\'ordonnance PDF', async () => {
      const rendezVous = await fabriquer.rendezVous();

      const creation = await api().post('/api/consultations').send({
        rendezVous: rendezVous.id, diagnostic: 'Bronchite', traitement: 'Amoxicilline 1 g\nParacétamol 1 g',
      });
      assert.equal(creation.status, 201);

      const doublon = await api().post('/api/consultations').send({ rendezVous: rendezVous.id });
      assert.equal(doublon.status, 409);

      const affichage = await api().get(`/api/consultations/${creation.body._id}`);
      assert.ok(affichage.body.rendezVous.patient.nom);

      const pdf = await api().get(`/api/consultations/${creation.body._id}/ordonnance`).buffer(true).parse((res, fin) => {
        const morceaux = [];
        res.on('data', (morceau) => morceaux.push(morceau));
        res.on('end', () => fin(null, Buffer.concat(morceaux)));
      });
      assert.equal(pdf.status, 200);
      assert.match(pdf.headers['content-type'], /application\/pdf/);
      assert.equal(pdf.body.subarray(0, 4).toString(), '%PDF');

      const modification = await api().put(`/api/consultations/${creation.body._id}`).send({ observations: 'RAS' });
      assert.equal(modification.body.observations, 'RAS');
    });

    it('bloque la suppression d\'un rendez-vous qui a une consultation, puis supprime l\'historique avec lui', async () => {
      const rendezVous = await fabriquer.rendezVous();
      await rendezVous.changerEtat('CONFIRME');
      await modeles.Notification.create({ rendezVous: rendezVous._id, type: 'CONFIRMATION' });
      const consultation = await modeles.Consultation.create({ rendezVous: rendezVous._id });

      assert.equal((await api().delete(`/api/rendez-vous/${rendezVous.id}`)).status, 409);

      assert.equal((await api().delete(`/api/consultations/${consultation.id}`)).status, 200);
      assert.equal((await api().delete(`/api/rendez-vous/${rendezVous.id}`)).status, 200);
      assert.equal(await modeles.HistoriqueEtat.countDocuments(), 0);
      assert.equal(await modeles.Notification.countDocuments(), 0);
    });
  });

  describe('Notification', () => {
    it('crée, liste, modifie et supprime une notification', async () => {
      const rendezVous = await fabriquer.rendezVous();

      const creation = await api().post('/api/notifications').send({ rendezVous: rendezVous.id, type: 'RAPPEL' });
      assert.equal(creation.status, 201);
      assert.equal(creation.body.envoyee, false);

      const nonEnvoyees = await api().get('/api/notifications?envoyee=false');
      assert.equal(nonEnvoyees.body.length, 1);

      const modification = await api().put(`/api/notifications/${creation.body._id}`).send({ envoyee: true, dateEnvoi: a('08:00') });
      assert.equal(modification.body.envoyee, true);

      assert.equal((await api().delete(`/api/notifications/${creation.body._id}`)).status, 200);
      assert.equal((await api().get(`/api/notifications/${creation.body._id}`)).status, 404);
    });

    it('refuse un type inconnu et un rendez-vous inexistant (400)', async () => {
      const reponse = await api().post('/api/notifications').send({ rendezVous: idInconnu(), type: 'SMS' });
      assert.equal(reponse.status, 400);
      assert.deepEqual(Object.keys(reponse.body.erreurs).sort(), ['rendezVous', 'type']);
    });
  });
});
