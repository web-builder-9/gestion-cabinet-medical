/**
 * Tests sans base de données : règles écrites en JavaScript et réponses d'erreur d'Express.
 */
const { describe, it } = require('node:test');
const assert = require('node:assert/strict');
const jwt = require('jsonwebtoken');

const { api, modeles } = require('./aide');
const { creerJeton, verifierJeton, cleSecrete } = require('../src/utils/jeton');
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

describe('Jeton JWT', () => {
  it('contient l\'identifiant et le rôle, et se vérifie avec la même clé', () => {
    const contenu = verifierJeton(creerJeton({ id: '6b09bd1e8a417e553d9f0a54', role: 'MEDECIN' }));
    assert.equal(contenu.sub, '6b09bd1e8a417e553d9f0a54');
    assert.equal(contenu.role, 'MEDECIN');
    assert.ok(contenu.exp > contenu.iat, 'le jeton a une date d\'expiration');
  });

  it('refuse un jeton signé avec une autre clé ou non signé', () => {
    assert.throws(() => verifierJeton(jwt.sign({ sub: 'x' }, 'une-autre-cle')), { name: 'JsonWebTokenError' });
    assert.throws(() => verifierJeton(jwt.sign({ sub: 'x' }, null, { algorithm: 'none' })), { name: 'JsonWebTokenError' });
  });

  it('protège GET /api/utilisateurs/moi : sans jeton, jeton faux ou expiré → 401', async () => {
    const expire = jwt.sign({ sub: 'x', exp: Math.floor(Date.now() / 1000) - 60 }, cleSecrete());

    const sansJeton = await api().get('/api/utilisateurs/moi');
    const jetonFaux = await api().get('/api/utilisateurs/moi').set('Authorization', 'Bearer abc');
    const jetonExpire = await api().get('/api/utilisateurs/moi').set('Authorization', `Bearer ${expire}`);

    assert.equal(sansJeton.status, 401);
    assert.equal(jetonFaux.status, 401);
    assert.equal(jetonExpire.status, 401);
    assert.match(jetonExpire.body.message, /expiré/);
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
