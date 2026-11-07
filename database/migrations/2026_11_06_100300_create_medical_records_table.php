<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « DossierMedical » : composition avec Patient (0..1 par patient).
     * Accès réservé aux médecins (RG-21).
     */
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('blood_type', 3)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable(); // antécédents
            $table->text('current_treatments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
