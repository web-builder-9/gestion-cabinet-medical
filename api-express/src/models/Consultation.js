const mongoose = require('mongoose');
const PDFDocument = require('pdfkit');

const { doitExister } = require('./validateurs');
const { dateSeule } = require('../utils/dates');

/**
 * Classe Consultation : compte rendu du médecin pour un rendez-vous (1 rendez-vous → 0..1 consultation).
 */
const consultationSchema = new mongoose.Schema(
  {
    date: { type: Date, default: Date.now },
    motif: { type: String, trim: true },
    observations: { type: String, trim: true },
    diagnostic: { type: String, trim: true },
    traitement: { type: String, trim: true }, // une ligne par médicament, imprimée sur l'ordonnance
    rendezVous: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'RendezVous',
      required: [true, 'Le rendez-vous est obligatoire.'],
      unique: true, // au plus une consultation par rendez-vous
      validate: doitExister('RendezVous', 'Rendez-vous introuvable.'),
    },
  },
  { versionKey: false },
);

/**
 * Méthode genererOrdonnancePDF() du diagramme : écrit l'ordonnance au format PDF dans « destination »
 * (la réponse HTTP). La consultation doit être chargée avec son rendez-vous, son patient et son médecin
 * (voir consultationController.ordonnance).
 */
consultationSchema.methods.genererOrdonnancePDF = function (destination) {
  const { patient, medecin } = this.rendezVous;
  const pdf = new PDFDocument({ size: 'A5', margin: 40 });
  pdf.pipe(destination);

  pdf.font('Helvetica-Bold').fontSize(14).text(`Dr ${medecin.prenom} ${medecin.nom}`);
  pdf.font('Helvetica').fontSize(10).text(medecin.specialite?.libelle ?? '');
  pdf.text(`Tél. ${medecin.telephone}`);

  pdf.moveDown(2);
  pdf.font('Helvetica-Bold').fontSize(16).text('ORDONNANCE', { align: 'center' });
  pdf.moveDown();
  pdf.font('Helvetica').fontSize(10).text(`Djerba, le ${dateSeule(this.date)}`, { align: 'right' });
  pdf.moveDown();
  pdf.fontSize(11).text(`Patient : ${patient.prenom} ${patient.nom}`);
  pdf.moveDown(1.5);

  const lignes = (this.traitement ?? '').split('\n').map((ligne) => ligne.trim()).filter(Boolean);
  if (lignes.length === 0) {
    pdf.text('Aucun traitement prescrit.');
  }
  lignes.forEach((ligne, index) => {
    pdf.text(`${index + 1}. ${ligne}`);
    pdf.moveDown(0.5);
  });

  pdf.moveDown(3);
  pdf.text('Signature et cachet du médecin', { align: 'right' });

  pdf.end();
};

module.exports = mongoose.model('Consultation', consultationSchema, 'consultations');
