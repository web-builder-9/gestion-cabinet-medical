<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classe « Notification » : journal des e-mails envoyés pour un rendez-vous
     * (BF-28, BF-29). Nommée appointment_notifications car Laravel réserve
     * « notifications » à son propre canal en base de données.
     */
    public function up(): void
    {
        Schema::create('appointment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // enum NotificationType
            $table->boolean('is_sent')->default(false); // « envoyee »
            $table->dateTime('sent_at')->nullable();    // « dateEnvoi »
            $table->timestamps();

            $table->index(['appointment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notifications');
    }
};
