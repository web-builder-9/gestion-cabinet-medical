<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_doctor_sees_the_staff_dashboard_and_their_role(): void
    {
        $doctor = User::factory()->doctor()->create(['name' => 'Dr Test']);

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour Dr Test')
            ->assertSee('Médecin')
            ->assertSee('Les indicateurs du cabinet')
            ->assertDontSee('Votre espace patient');
    }

    public function test_the_secretary_sees_the_staff_dashboard(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Secrétaire')
            ->assertSee('Les indicateurs du cabinet');
    }

    public function test_a_patient_sees_the_patient_message_only(): void
    {
        $this->actingAs(User::factory()->patient()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Votre espace patient')
            ->assertDontSee('Les indicateurs du cabinet');
    }
}
