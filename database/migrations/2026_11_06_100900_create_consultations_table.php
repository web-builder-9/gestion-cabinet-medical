<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Consultation » : une consultation par rendez-vous terminé (RG-19).
     * Le patient et le médecin se déduisent du rendez-vous.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->dateTime('consulted_at');
            $table->string('reason'); // motif
            $table->text('observations')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable(); // traitement et recommandations
            $table->timestamps();
        });

        // Lignes de l'ordonnance (maquette 14) : seul ajout par rapport aux 12 classes.
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('medication', 150);
            $table->string('dosage', 50)->nullable();
            $table->string('posology'); // posologie
            $table->string('duration', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('consultations');
    }
};
