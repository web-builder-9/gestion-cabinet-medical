<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Tableau de bord du secrétariat (maquette 7) : chiffres du jour, demandes à confirmer,
 * rendez-vous à déplacer et planning du jour (BF-15).
 */
class SecretaryDashboardTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
    }

    public function test_the_secretary_dashboard_shows_the_day_and_the_requests(): void
    {
        $this->travelTo($this->thursday('09:40'));
        $karim = Patient::factory()->withUser(['first_name' => 'Karim', 'last_name' => 'Trabelsi'])->create();
        Appointment::factory()->for($karim)->for($this->doctor)->at($this->thursday('08:30'))->noShow()->create();
        Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('09:00'))->completed()->create();
        Appointment::factory()->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();
        $request = Appointment::factory()->for($this->doctor)->at(now()->addDays(4)->setTime(10, 30))->pending()->create();

        $this->actingAs($this->secretary)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour Nadia')
            ->assertSee('Jeudi 5 novembre 2026 · planning du jour des 1 médecin')
            ->assertSeeInOrder(['Rendez-vous aujourd\'hui', '3', '1 terminé · 1 à venir'], false)
            ->assertSeeInOrder(['Demandes en ligne à confirmer', $request->patient->full_name, 'Confirmer'])
            ->assertSeeInOrder(['Planning du jour', '08:30', 'K. Trabelsi', 'Absent', '09:00', 'H. Mansouri', 'Terminé'])
            ->assertSee('Libre');
    }

    public function test_appointments_falling_on_an_unavailability_are_listed_to_move(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();

        $this->actingAs($this->secretary)->get('/dashboard')->assertDontSee('Rendez-vous à déplacer');

        $this->doctor->unavailabilities()->create(['starts_at' => $this->thursday('08:00'), 'ends_at' => $this->thursday('12:00'), 'reason' => 'Congé']);

        $this->actingAs($this->secretary)
            ->get('/dashboard')
            ->assertSeeInOrder(['Rendez-vous à déplacer', 'Hedi Mansouri', route('appointments.edit', $appointment)]);
    }
}
