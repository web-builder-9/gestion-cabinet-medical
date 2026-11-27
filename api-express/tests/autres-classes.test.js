/**
 * Specialite, DossierMedical, Disponibilite, Indisponibilite et ParametresCabinet.
 */
const { describe, it, before, after, beforeEach } = require('node:test');
const assert = require('node:assert/strict');

const { api, connecter, viderBase, deconnecter, sansBase, fabriquer, idInconnu } = require('./aide');

describe('Autres classes', { skip: sansBase }, () => {
  before(connecter);
  after(deconnecter);
  beforeEach(viderBase);

  describe('Specialite', () => {
    it('crée, liste, affiche, modifie et supprime une spécialité', async () => {
      const creation = await api().post('/api/specialites').send({ libelle: ' Dermatologie ', description: 'Peau' });
      assert.equal(creation.status, 201);
      assert.equal(creation.body.libelle, 'Dermatologie'); // espaces retirés (trim)
      const id = creation.body._id;

      assert.equal((await api().get('/api/specialites')).body.length, 1);
      assert.equal((await api().get(`/api/specialites/${id}`)).body.description, 'Peau');

      const modification = await api().put(`/api/specialites/${id}`).send({ description: 'Peau, cheveux et ongles' });
      assert.equal(modification.body.description, 'Peau, cheveux et ongles');

      assert.equal((await api().delete(`/api/specialites/${id}`)).status, 200);
      assert.equal((await api().get(`/api/specialites/${id}`)).status, 404);
    });

    it('refuse un libellé manquant (400) ou déjà utilisé (409)', async () => {
      await fabriquer.specialite({ libelle: 'Pédiatrie' });

      assert.equal((await api().post('/api/specialites').send({})).status, 400);
      assert.equal((await api().post('/api/specialites').send({ libelle: 'Pédiatrie' })).status, 409);
    });

    it('refuse de supprimer une spécialité exercée par un médecin (409)', async () => {
      const medecin = await fabriquer.medecin();

      assert.equal((await api().delete(`/api/specialites/${medecin.specialite}`)).status, 409);
    });

    it('répond 404 pour un identifiant inconnu et 400 pour un identifiant mal formé', async () => {
      assert.equal((await api().put(`/api/specialites/${idInconnu()}`).send({ libelle: 'X' })).status, 404);
      assert.equal((await api().delete('/api/specialites/abc')).status, 400);
    });
  });

  describe('DossierMedical', () => {
    it('crée un seul dossier par patient, le modifie et le supprime', async () => {
      const patient = await fabriquer.patient({ nom: 'Ben Salah' });

      const creation = await api().post('/api/dossiers-medicaux').send({ patient: patient.id, groupeSanguin: 'O+', allergies: 'Pénicilline' });
      assert.equal(creation.status, 201);

      const second = await api().post('/api/dossiers-medicaux').send({ patient: patient.id });
      assert.equal(second.status, 409);

      const affichage = await api().get(`/api/dossiers-medicaux/${creation.body._id}`);
      assert.equal(affichage.body.patient.nom, 'Ben Salah');

      const filtre = await api().get(`/api/dossiers-medicaux?patient=${patient.id}`);
      assert.equal(filtre.body.length, 1);

      const modification = await api().put(`/api/dossiers-medicaux/${creation.body._id}`).send({ antecedents: 'Asthme', patient: idInconnu() });
      assert.equal(modification.status, 200);
      assert.equal(modification.body.antecedents, 'Asthme');
      assert.equal(modification.body.patient, patient.id); // un dossier ne change pas de patient

      assert.equal((await api().delete(`/api/dossiers-medicaux/${creation.body._id}`)).status, 200);
    });

    it('refuse un patient inconnu et un groupe sanguin invalide (400)', async () => {
      const reponse = await api().post('/api/dossiers-medicaux').send({ patient: idInconnu(), groupeSanguin: 'C+' });
      assert.equal(reponse.status, 400);
      assert.deepEqual(Object.keys(reponse.body.erreurs).sort(), ['groupeSanguin', 'patient']);
    });
  });

  describe('Disponibilite', () => {
    it('crée, liste, modifie et supprime une disponibilité', async () => {
      const medecin = await fabriquer.medecin();

      const creation = await api().post('/api/disponibilites').send({
        medecin: medecin.id, jour: 'LUNDI', heureDebut: '08:30', heureFin: '12:30', dureeCreneau: 30,
      });
      assert.equal(creation.status, 201);

      assert.equal((await api().get(`/api/disponibilites?medecin=${medecin.id}`)).body.length, 1);

      const modification = await api().put(`/api/disponibilites/${creation.body._id}`).send({ heureFin: '13:00' });
      assert.equal(modification.status, 200);
      assert.equal(modification.body.heureFin, '13:00');

      assert.equal((await api().delete(`/api/disponibilites/${creation.body._id}`)).status, 200);
    });

    it('refuse des heures invalides (400) et deux plages qui se recouvrent le même jour (409)', async () => {
      const medecin = await fabriquer.medecin();
      const plage = { medecin: medecin.id, jour: 'MARDI', heureDebut: '08:00', heureFin: '12:00', dureeCreneau: 30 };
      await api().post('/api/disponibilites').send(plage);

      const invalide = await api().post('/api/disponibilites').send({ ...plage, heureDebut: '14:00', heureFin: '13:00' });
      assert.equal(invalide.status, 400);

      const recouvrement = await api().post('/api/disponibilites').send({ ...plage, heureDebut: '11:00', heureFin: '13:00' });
      assert.equal(recouvrement.status, 409);

      const autreJour = await api().post('/api/disponibilites').send({ ...plage, jour: 'JEUDI' });
      assert.equal(autreJour.status, 201);
    });
  });

  describe('Indisponibilite', () => {
    it('crée, affiche, modifie et supprime une indisponibilité', async () => {
      const medecin = await fabriquer.medecin({ nom: 'Ammar' });

      const creation = await api().post('/api/indisponibilites').send({
        medecin: medecin.id, debut: '2026-12-21T00:00:00+01:00', fin: '2026-12-24T23:59:00+01:00', motif: 'Congrès',
      });
      assert.equal(creation.status, 201);

      assert.equal((await api().get(`/api/indisponibilites/${creation.body._id}`)).body.medecin.nom, 'Ammar');

      const modification = await api().put(`/api/indisponibilites/${creation.body._id}`).send({ motif: 'Formation' });
      assert.equal(modification.body.motif, 'Formation');

      assert.equal((await api().delete(`/api/indisponibilites/${creation.body._id}`)).status, 200);
    });

    it('refuse une fin avant le début (400)', async () => {
      const medecin = await fabriquer.medecin();

      const reponse = await api().post('/api/indisponibilites').send({
        medecin: medecin.id, debut: '2026-12-24T00:00:00+01:00', fin: '2026-12-21T00:00:00+01:00',
      });
      assert.equal(reponse.status, 400);
      assert.ok(reponse.body.erreurs.fin);
    });
  });

  describe('ParametresCabinet', () => {
    it('crée les paramètres avec leurs valeurs par défaut, une seule fois', async () => {
      const creation = await api().post('/api/parametres-cabinet').send({});
      assert.equal(creation.status, 201);
      assert.equal(creation.body.heureOuverture, '08:00');
      assert.equal(creation.body.dureeCreneauDefaut, 30);

      assert.equal((await api().post('/api/parametres-cabinet').send({})).status, 409);
      assert.equal((await api().get('/api/parametres-cabinet')).body.length, 1);

      const modification = await api().put(`/api/parametres-cabinet/${creation.body._id}`).send({ heureFermeture: '19:00' });
      assert.equal(modification.body.heureFermeture, '19:00');

      const invalide = await api().put(`/api/parametres-cabinet/${creation.body._id}`).send({ heureOuverture: '20:00' });
      assert.equal(invalide.status, 400);

      assert.equal((await api().delete(`/api/parametres-cabinet/${creation.body._id}`)).status, 200);
    });
  });
});
