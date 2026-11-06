<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « ParametresCabinet » : une seule ligne, modifiée par l'administrateur (BF-26).
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('cabinet_name', 150)->default('Cabinet médical');
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->time('opening_time')->default('08:00');  // lundi à vendredi
            $table->time('closing_time')->default('17:00');
            $table->time('saturday_opening_time')->nullable(); // null = fermé le samedi
            $table->time('saturday_closing_time')->nullable();
            $table->unsignedSmallInteger('default_slot_minutes')->default(30);
            $table->unsignedSmallInteger('cancellation_hours')->default(24);  // RG-16
            $table->unsignedSmallInteger('booking_days_ahead')->default(60);  // RG-14
            $table->unsignedSmallInteger('reminder_hours')->default(24);      // BF-29
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
