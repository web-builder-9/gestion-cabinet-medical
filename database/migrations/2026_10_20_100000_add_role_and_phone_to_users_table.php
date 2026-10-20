<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le rôle (médecin, secrétaire, patient) et le téléphone.
     *
     * Le rôle par défaut est « patient », le moins privilégié : un compte
     * créé via la page d'inscription publique n'a donc accès à aucune donnée
     * du cabinet. Les comptes du personnel sont créés par le médecin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('patient')->after('email')->index();
            $table->string('phone', 20)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone']);
        });
    }
};
