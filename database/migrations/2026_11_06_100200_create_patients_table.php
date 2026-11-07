<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Patient » : spécialise Utilisateur. Nom, prénom, e-mail et
     * téléphone sont dans users ; cette table porte les attributs propres au patient.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('cin', 8)->nullable()->unique();
            $table->date('birth_date');
            $table->string('gender', 1);
            $table->string('address')->nullable();
            // Loi organique n° 2004-63 : consentement au traitement des données de santé.
            $table->boolean('data_consent')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamps();
            // RG-22 : suppression logique (archivage), jamais physique.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
