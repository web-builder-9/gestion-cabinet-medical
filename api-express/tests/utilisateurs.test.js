/**
 * Utilisateur, Patient et Medecin (héritage), connexion.
 */
const { describe, it, before, after, beforeEach } = require('node:test');
const assert = require('node:assert/strict');

const { api, connecter, viderBase, deconnecter, sansBase, fabriquer, idInconnu, modeles } = require('./aide');

const nouveauPatient = (donnees = {}) => ({
  nom: 'Ben Salah', prenom: 'Amira', email: 'amira@test.tn', motDePasse: 'password', telephone: '98451220',
  cin: '08412337', dateNaissance: '1992-06-14', genre: 'F', adresse: 'Midoun, Djerba', ...donnees,
});

describe('Utilisateurs, patients et médecins', { skip: sansBase }, () => {
  before(connecter);
  after(deconnecter);
  beforeEach(viderBase);

  describe('Utilisateur (personnel)', () => {
    it('crée une secrétaire sans jamais renvoyer le mot de passe, qui est haché en base', async () => {
      const reponse = await api().post('/api/utilisateurs').send({
        nom: 'Jlassi', prenom: 'Nadia', email: 'Nadia@Test.tn', motDePasse: 'password', telephone: '20100300', role: 'SECRETAIRE',
      });

      assert.equal(reponse.status, 201);
      assert.equal(reponse.body.role, 'SECRETAIRE');
      assert.equal(reponse.body.email, 'nadia@test.tn');
      assert.equal(reponse.body.telephone, '20 100 300');
      assert.equal(reponse.body.actif, true);
      assert.equal(reponse.body.motDePasse, undefined);

      const enBase = await modeles.Utilisateur.findById(reponse.body._id).select('+motDePasse');
      assert.notEqual(enBase.motDePasse, 'password');
      assert.match(enBase.motDePasse, /^\$2[aby]\$/); // empreinte bcrypt
    });

    it('refuse un e-mail déjà utilisé (409) et des champs manquants (400)', async () => {
      await fabriquer.secretaire({ email: 'pris@test.tn' });

      const doublon = await api().post('/api/utilisateurs').send({
        nom: 'X', prenom: 'Y', email: 'pris@test.tn', motDePasse: 'password', telephone: '20999999', role: 'ADMINISTRATEUR',
      });
      assert.equal(doublon.status, 409);

      const incomplet = await api().post('/api/utilisateurs').send({ role: 'SECRETAIRE', telephone: '12' });
      assert.equal(incomplet.status, 400);
      assert.deepEqual(Object.keys(incomplet.body.erreurs).sort(), ['email', 'motDePasse', 'nom', 'prenom', 'telephone']);
    });

    it('liste tous les comptes de la collection et filtre par rôle', async () => {
      await fabriquer.secretaire();
      await fabriquer.patient();
      await fabriquer.medecin();

      const tous = await api().get('/api/utilisateurs');
      assert.equal(tous.status, 200);
      assert.deepEqual(tous.body.map((u) => u.role).sort(), ['MEDECIN', 'PATIENT', 'SECRETAIRE']);
      assert.ok(tous.body.find((u) => u.role === 'PATIENT').cin, 'le patient garde ses attributs propres');

      const patients = await api().get('/api/utilisateurs?role=PATIENT');
      assert.equal(patients.body.length, 1);
    });

    it('modifie un compte sans toucher à son mot de passe ni à son rôle', async () => {
      const patient = await fabriquer.patient({ email: 'karim@test.tn' });

      const reponse = await api().put(`/api/utilisateurs/${patient.id}`).send({ actif: false, role: 'ADMINISTRATEUR', prenom: 'Karim' });
      assert.equal(reponse.status, 200);
      assert.equal(reponse.body.actif, false);
      assert.equal(reponse.body.role, 'PATIENT');
      assert.equal(reponse.body.prenom, 'Karim');

      await api().put(`/api/utilisateurs/${patient.id}`).send({ actif: true });
      const connexion = await api().post('/api/utilisateurs/connexion').send({ email: 'karim@test.tn', motDePasse: 'password' });
      assert.equal(connexion.status, 200);
    });

    it('supprime un compte du personnel, mais renvoie les patients vers /api/patients', async () => {
      const secretaire = await fabriquer.secretaire();
      const patient = await fabriquer.patient();

      assert.equal((await api().delete(`/api/utilisateurs/${patient.id}`)).status, 409);
      assert.equal((await api().delete(`/api/utilisateurs/${secretaire.id}`)).status, 200);
      assert.equal((await api().get(`/api/utilisateurs/${secretaire.id}`)).status, 404);
    });
  });

  describe('seConnecter() : POST /api/utilisateurs/connexion', () => {
    it('connecte avec le bon mot de passe', async () => {
      await fabriquer.secretaire({ email: 'nadia@test.tn' });

      const reponse = await api().post('/api/utilisateurs/connexion').send({ email: 'NADIA@test.tn', motDePasse: 'password' });
      assert.equal(reponse.status, 200);
      assert.equal(reponse.body.connecte, true);
      assert.equal(reponse.body.utilisateur.email, 'nadia@test.tn');
      assert.equal(reponse.body.utilisateur.motDePasse, undefined);
    });

    it('refuse un mauvais mot de passe, un compte désactivé (RG-02) et une injection NoSQL', async () => {
      await fabriquer.secretaire({ email: 'nadia@test.tn' });
      await fabriquer.secretaire({ email: 'hela@test.tn', actif: false });

      const mauvais = await api().post('/api/utilisateurs/connexion').send({ email: 'nadia@test.tn', motDePasse: 'autre' });
      const desactive = await api().post('/api/utilisateurs/connexion').send({ email: 'hela@test.tn', motDePasse: 'password' });
      const injection = await api().post('/api/utilisateurs/connexion').send({ email: { $ne: null }, motDePasse: 'password' });

      assert.equal(mauvais.status, 401);
      assert.equal(desactive.status, 401);
      assert.equal(injection.status, 401);
    });
  });

  describe('Patient', () => {
    it('crée, liste, affiche, modifie et supprime un patient', async () => {
      const creation = await api().post('/api/patients').send(nouveauPatient());
      assert.equal(creation.status, 201);
      assert.equal(creation.body.role, 'PATIENT');
      assert.equal(creation.body.telephone, '98 451 220');
      const id = creation.body._id;

      const liste = await api().get('/api/patients');
      assert.equal(liste.body.length, 1);

      const affichage = await api().get(`/api/patients/${id}`);
      assert.equal(affichage.body.cin, '08412337');

      const modification = await api().put(`/api/patients/${id}`).send({ adresse: 'Houmt Souk, Djerba', cin: '08412338' });
      assert.equal(modification.status, 200);
      assert.equal(modification.body.adresse, 'Houmt Souk, Djerba');

      const suppression = await api().delete(`/api/patients/${id}`);
      assert.equal(suppression.status, 200);
      assert.equal((await api().get(`/api/patients/${id}`)).status, 404);
    });

    it('impose le rôle PATIENT même si le JSON en envoie un autre', async () => {
      const reponse = await api().post('/api/patients').send(nouveauPatient({ role: 'ADMINISTRATEUR' }));
      assert.equal(reponse.status, 201);
      assert.equal(reponse.body.role, 'PATIENT');
    });

    it('refuse un CIN déjà utilisé (409) et des données invalides (400)', async () => {
      await fabriquer.patient({ cin: '08412337' });

      const doublon = await api().post('/api/patients').send(nouveauPatient({ cin: '08412337' }));
      assert.equal(doublon.status, 409);

      const invalide = await api().post('/api/patients').send(nouveauPatient({
        email: 'autre@test.tn', telephone: '97000000', cin: '123', genre: 'X', dateNaissance: '2999-01-01',
      }));
      assert.equal(invalide.status, 400);
      assert.deepEqual(Object.keys(invalide.body.erreurs).sort(), ['cin', 'dateNaissance', 'genre']);
    });

    it('supprime le dossier médical avec le patient (composition)', async () => {
      const patient = await fabriquer.patient();
      await modeles.DossierMedical.create({ patient: patient._id, groupeSanguin: 'O+' });

      await api().delete(`/api/patients/${patient.id}`);
      assert.equal(await modeles.DossierMedical.countDocuments(), 0);
    });

    it('refuse de supprimer un patient qui a des rendez-vous (409)', async () => {
      const rendezVous = await fabriquer.rendezVous();

      const reponse = await api().delete(`/api/patients/${rendezVous.patient}`);
      assert.equal(reponse.status, 409);
    });

    it('répond 404 pour un patient inconnu et pour un médecin demandé comme patient', async () => {
      const medecin = await fabriquer.medecin();

      assert.equal((await api().get(`/api/patients/${idInconnu()}`)).status, 404);
      assert.equal((await api().get(`/api/patients/${medecin.id}`)).status, 404);
    });
  });

  describe('Medecin', () => {
    it('crée un médecin et affiche sa spécialité (populate)', async () => {
      const specialite = await fabriquer.specialite({ libelle: 'Cardiologie' });

      const creation = await api().post('/api/medecins').send({
        nom: 'Ammar', prenom: 'Leila', email: 'leila@test.tn', motDePasse: 'password', telephone: '20200300',
        specialite: specialite.id, presentation: 'Bilans cardiaques',
      });
      assert.equal(creation.status, 201);
      assert.equal(creation.body.role, 'MEDECIN');

      const affichage = await api().get(`/api/medecins/${creation.body._id}`);
      assert.equal(affichage.body.specialite.libelle, 'Cardiologie');

      const autre = await fabriquer.specialite({ libelle: 'Pédiatrie' });
      const modification = await api().put(`/api/medecins/${creation.body._id}`).send({ specialite: autre.id });
      assert.equal(modification.body.specialite.libelle, 'Pédiatrie');

      const filtre = await api().get(`/api/medecins?specialite=${autre.id}`);
      assert.equal(filtre.body.length, 1);
    });

    it('refuse une spécialité inexistante (400)', async () => {
      const reponse = await api().post('/api/medecins').send({
        nom: 'Saidi', prenom: 'Mehdi', email: 'mehdi@test.tn', motDePasse: 'password', telephone: '20200200', specialite: idInconnu(),
      });
      assert.equal(reponse.status, 400);
      assert.equal(reponse.body.erreurs.specialite, 'Spécialité introuvable.');
    });

    it('supprime disponibilités et indisponibilités avec le médecin (compositions)', async () => {
      const medecin = await fabriquer.medecin();
      await modeles.Disponibilite.create({ medecin: medecin._id, jour: 'LUNDI', heureDebut: '08:00', heureFin: '12:00', dureeCreneau: 30 });
      await modeles.Indisponibilite.create({ medecin: medecin._id, debut: '2026-12-21T00:00:00+01:00', fin: '2026-12-24T00:00:00+01:00' });

      const reponse = await api().delete(`/api/medecins/${medecin.id}`);
      assert.equal(reponse.status, 200);
      assert.equal(await modeles.Disponibilite.countDocuments(), 0);
      assert.equal(await modeles.Indisponibilite.countDocuments(), 0);
    });

    it('refuse de supprimer un médecin qui a des rendez-vous (409)', async () => {
      const rendezVous = await fabriquer.rendezVous();

      assert.equal((await api().delete(`/api/medecins/${rendezVous.medecin}`)).status, 409);
    });
  });
});
