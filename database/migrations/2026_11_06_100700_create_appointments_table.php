<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « RendezVous ». Jamais supprimé : un rendez-vous annulé reste en base (RG-22).
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable(); // motif
            $table->string('status', 20)->default('pending'); // enum AppointmentStatus
            $table->text('notes')->nullable(); // notes de la secrétaire pour le médecin
            $table->timestamps(); // created_at = « creeLe »

            // Agenda d'un médecin, rendez-vous d'un patient, et détection des conflits.
            $table->index(['doctor_id', 'starts_at']);
            $table->index(['patient_id', 'starts_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
