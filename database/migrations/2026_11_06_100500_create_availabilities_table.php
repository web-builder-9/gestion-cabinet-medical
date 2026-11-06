<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Disponibilite » : plage hebdomadaire d'un médecin (RG-09).
     */
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = lundi … 6 = samedi (enum DayOfWeek)
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->timestamps();

            $table->index(['doctor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
