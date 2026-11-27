const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const { Role } = require('./enums');
const { formaterTelephone } = require('./validateurs');

/**
 * Classe Utilisateur (classe mère de Patient et Medecin).
 *
 * Héritage avec les « discriminateurs » Mongoose : patients, médecins, secrétaires et administrateurs
 * sont tous dans la collection « utilisateurs ». Le champ « role » indique la sous-classe :
 * PATIENT → Patient (models/Patient.js), MEDECIN → Medecin (models/Medecin.js),
 * SECRETAIRE et ADMINISTRATEUR → Utilisateur simple (pas d'attribut en plus).
 */
const utilisateurSchema = new mongoose.Schema(
  {
    nom: { type: String, required: [true, 'Le nom est obligatoire.'], trim: true },
    prenom: { type: String, required: [true, 'Le prénom est obligatoire.'], trim: true },
    email: {
      type: String,
      required: [true, "L'e-mail est obligatoire."],
      unique: true, // index unique : deux comptes ne peuvent pas avoir le même e-mail
      trim: true,
      lowercase: true,
      match: [/^\S+@\S+\.\S+$/, "L'e-mail n'est pas valide."],
    },
    motDePasse: {
      type: String,
      required: [true, 'Le mot de passe est obligatoire.'],
      minlength: [8, 'Le mot de passe doit contenir au moins 8 caractères.'],
      select: false, // jamais renvoyé par les requêtes, sauf si on le demande avec .select('+motDePasse')
    },
    telephone: {
      type: String,
      required: [true, 'Le téléphone est obligatoire.'],
      unique: true,
      set: formaterTelephone, // « 22123456 » est enregistré « 22 123 456 »
      match: [/^\d{2} \d{3} \d{3}$/, 'Le téléphone doit contenir 8 chiffres, par exemple 22 123 456.'],
    },
    role: {
      type: String,
      required: [true, 'Le rôle est obligatoire.'],
      enum: { values: Object.values(Role), message: 'Rôle inconnu : {VALUE}.' },
    },
    actif: { type: Boolean, default: true }, // RG-02 : un compte désactivé ne peut plus se connecter
  },
  {
    discriminatorKey: 'role', // le rôle choisit la sous-classe
    versionKey: false, // pas de champ technique « __v » dans les réponses
    toJSON: {
      // Sécurité : le mot de passe (même haché) ne sort jamais dans une réponse JSON.
      transform: (document, objet) => {
        delete objet.motDePasse;
        return objet;
      },
    },
  },
);

/**
 * Avant chaque enregistrement : si le mot de passe a été saisi ou modifié, on enregistre son
 * empreinte bcrypt à la place (jamais le mot de passe en clair dans la base).
 * La validation (longueur minimale…) s'exécute avant ce hook, sur le mot de passe en clair.
 */
utilisateurSchema.pre('save', async function () {
  if (this.isModified('motDePasse')) {
    this.motDePasse = await bcrypt.hash(this.motDePasse, 10);
  }
});

/**
 * Méthode seConnecter() du diagramme : vrai si le mot de passe correspond et que le compte est actif.
 * Le document doit avoir été chargé avec .select('+motDePasse').
 */
utilisateurSchema.methods.seConnecter = async function (motDePasse) {
  if (!this.actif || typeof motDePasse !== 'string') {
    return false;
  }

  return bcrypt.compare(motDePasse, this.motDePasse);
};

module.exports = mongoose.model('Utilisateur', utilisateurSchema, 'utilisateurs');
