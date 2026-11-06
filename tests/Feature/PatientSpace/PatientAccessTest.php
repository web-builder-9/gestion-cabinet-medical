<?php

namespace Tests\Feature\PatientSpace;

use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Un patient déjà connu du cabinet reçoit l'accès à son espace (e-mail d'invitation).
 */
class PatientAccessTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
    }

    public function test_the_secretary_sends_the_portal_access_to_a_known_patient(): void
    {
        Notification::fake();

        $this->actingAs($this->secretary)
            ->post("/patients/{$this->patient->id}/invitation")
            ->assertSessionHas('status', "Un e-mail d'accès à l'espace patient a été envoyé à {$this->patient->user->email}.");

        Notification::assertSentTo($this->patient->user, AccountInvitation::class);
    }

    public function test_the_access_needs_an_email_address(): void
    {
        $this->patient->user->update(['email' => null]);

        $this->actingAs($this->secretary)
            ->post("/patients/{$this->patient->id}/invitation")
            ->assertSessionHas('error', 'Ajoutez d\'abord l\'adresse e-mail du patient.');
    }

    public function test_only_staff_send_the_access(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post("/patients/{$this->patient->id}/invitation")->assertForbidden();
        $this->actingAs($this->patient->user)->post("/patients/{$this->patient->id}/invitation")->assertForbidden();
    }
}
