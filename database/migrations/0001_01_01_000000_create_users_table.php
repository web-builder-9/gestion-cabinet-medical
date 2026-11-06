<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Classe « Utilisateur » du diagramme : attributs communs à tous les rôles.
        // Patient et Médecin la spécialisent (tables patients et doctors, liées en 1-1).
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            // Facultative pour un patient créé au cabinet sans e-mail (pas d'accès en ligne).
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 20)->nullable()->unique(); // RG-05
            $table->string('password');
            $table->string('role', 20)->default('patient')->index(); // RG-01 : un seul rôle
            $table->boolean('is_active')->default(true); // RG-02 : un compte désactivé ne se connecte plus
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
