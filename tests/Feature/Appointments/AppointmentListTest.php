<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Liste des rendez-vous du cabinet, avec ses filtres (BF-14, BF-15).
 */
class AppointmentListTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
    }

    public function test_the_list_shows_upcoming_appointments_and_filters_by_state(): void
    {
        Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:00'))->pending()->create();
        $amira = Patient::factory()->withUser(['first_name' => 'Amira', 'last_name' => 'Ben Salah'])->create();
        Appointment::factory()->for($amira)->for($this->doctor)->at($this->thursday('11:00'))->confirmed()->create();

        $this->actingAs($this->secretary)
            ->get('/rendez-vous')
            ->assertOk()
            ->assertSee('Hedi Mansouri')
            ->assertSee('Amira Ben Salah')
            ->assertSee('1 à confirmer');

        $this->actingAs($this->secretary)
            ->get('/rendez-vous?etat=pending')
            ->assertSee('Hedi Mansouri')
            ->assertDontSee('Amira Ben Salah');

        $this->actingAs($this->secretary)
            ->get('/rendez-vous?q=Ben+Salah')
            ->assertSee('Amira Ben Salah')
            ->assertDontSee('Hedi Mansouri');
    }
}
