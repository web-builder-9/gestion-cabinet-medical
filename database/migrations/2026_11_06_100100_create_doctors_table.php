<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Medecin » : spécialise Utilisateur (héritage = une table par
     * classe, reliée à users en 1-1 par user_id unique).
     */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            // RG-06 : une seule spécialité. RG-07 : restrictOnDelete empêche, au niveau
            // de la base, de supprimer une spécialité encore attribuée à un médecin.
            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->text('presentation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
