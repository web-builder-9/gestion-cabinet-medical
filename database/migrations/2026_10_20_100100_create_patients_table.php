<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche administrative du patient (identité, coordonnées).
     * Les données médicales sont dans medical_records, pour pouvoir
     * en restreindre l'accès au médecin.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // Compte utilisateur du patient (fonctionnalité prévue plus tard).
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('cin', 8)->nullable()->unique();
            $table->date('birth_date');
            $table->string('gender', 1);
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
            // Suppression « douce » : on ne perd jamais l'historique médical.
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
