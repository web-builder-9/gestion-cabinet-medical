<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Indisponibilite » : absence ponctuelle d'un médecin (congé, formation…).
     */
    public function up(): void
    {
        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
