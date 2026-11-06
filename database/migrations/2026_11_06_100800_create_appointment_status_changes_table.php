<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « HistoriqueEtat » (RG-23, BF-30) : chaque changement d'état d'un
     * rendez-vous, avec la date, l'auteur, l'ancien et le nouvel état.
     */
    public function up(): void
    {
        Schema::create('appointment_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            // Auteur du changement ; null = action automatique du système.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('old_status', 20)->nullable(); // null à la création
            $table->string('new_status', 20);
            $table->dateTime('changed_at');

            $table->index(['appointment_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_status_changes');
    }
};
