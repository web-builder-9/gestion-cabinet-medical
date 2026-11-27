/**
 * Tests sans base de données : règles écrites en JavaScript et réponses d'erreur d'Express.
 */
const { describe, it } = require('node:test');
const assert = require('node:assert/strict');

const { api, modeles } = require('./aide');
const { TRANSITIONS, ETATS_ACTIFS } = require('../src/models/enums');
const { formaterTelephone } = require('../src/models/validateurs');
const choisir = require('../src/utils/choisir');

describe('RendezVous.chevauche() (RG-12)', () => {
  const rendezVous = new modeles.RendezVous({ debut: '2026-12-07T09:00:00+01:00', fin: '2026-12-07T09:30:00+01:00' });
  const creneau = (debut, fin) => ({ debut: new Date(`2026-12-07T${debut}:00+01:00`), fin: new Date(`2026-12-07T${fin}:00+01:00`) });

  it('détecte un créneau qui commence pendant le rendez-vous', () => {
    assert.equal(rendezVous.chevauche(creneau('09:15', '09:45')), true);
  });

  it('détecte un créneau qui englobe le rendez-vous', () => {
    assert.equal(rendezVous.chevauche(creneau('08:00', '10:00')), true);
  });

  it('accepte deux créneaux qui se suivent', () => {
    assert.equal(rendezVous.chevauche(creneau('09:30', '10:00')), false);
    assert.equal(rendezVous.chevauche(creneau('08:30', '09:00')), false);
  });
});

describe('Transitions d\'état (RG-17)', () => {
  it('Annulé, Terminé et Absent sont des états finaux', () => {
    assert.deepEqual(TRANSITIONS.ANNULE, []);
    assert.deepEqual(TRANSITIONS.TERMINE, []);
    assert.deepEqual(TRANSITIONS.ABSENT, []);
  });

  it('seuls En attente et Confirmé occupent un créneau', () => {
    assert.deepEqual([...ETATS_ACTIFS], ['EN_ATTENTE', 'CONFIRME']);
  });
});

describe('Outils', () => {
  it('formate un téléphone à 8 chiffres', () => {
    assert.equal(formaterTelephone('22123456'), '22 123 456');
    assert.equal(formaterTelephone('22-123-456'), '22 123 456');
    assert.equal(formaterTelephone('2212'), '2212');
  });

  it('ne garde que les champs autorisés', () => {
    assert.deepEqual(choisir({ libelle: 'Pédiatrie', _id: 'x', role: 'ADMIN' }, ['libelle', 'description']), { libelle: 'Pédiatrie' });
    assert.deepEqual(choisir(undefined, ['libelle']), {});
  });

  it('valide les heures d\'une disponibilité', async () => {
    const disponibilite = new modeles.Disponibilite({ jour: 'DIMANCHE', heureDebut: '12:00', heureFin: '8h', dureeCreneau: 7.5 });
    // Seulement ces champs : le médecin demanderait une requête à MongoDB.
    await assert.rejects(disponibilite.validate(['jour', 'heureFin', 'dureeCreneau']), (erreur) => {
      assert.deepEqual(Object.keys(erreur.errors).sort(), ['dureeCreneau', 'heureFin', 'jour']);
      return true;
    });
  });
});

describe('Réponses d\'Express sans base de données', () => {
  it('GET /api liste les 12 ressources', async () => {
    const reponse = await api().get('/api');
    assert.equal(reponse.status, 200);
    assert.equal(reponse.body.ressources.length, 12);
  });

  it('répond 404 sur une route inconnue', async () => {
    const reponse = await api().get('/api/factures');
    assert.equal(reponse.status, 404);
  });

  it('répond 400 sur un JSON mal formé', async () => {
    const reponse = await api().post('/api/specialites').set('Content-Type', 'application/json').send('{"libelle": }');
    assert.equal(reponse.status, 400);
  });

  it('répond 400 sur un identifiant mal formé', async () => {
    const reponse = await api().get('/api/patients/123');
    assert.equal(reponse.status, 400);
  });

  it('refuse de créer un patient par /api/utilisateurs', async () => {
    const reponse = await api().post('/api/utilisateurs').send({ role: 'PATIENT' });
    assert.equal(reponse.status, 400);
  });
});
