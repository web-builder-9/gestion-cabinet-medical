<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Agenda par jour et par semaine (maquette 11) : BF-19 pour le médecin, BF-15 pour la secrétaire.
 */
class AgendaTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
        $karim = Patient::factory()->withUser(['first_name' => 'Karim', 'last_name' => 'Trabelsi'])->create();
        Appointment::factory()->for($karim)->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();
    }

    public function test_the_doctor_sees_their_week(): void
    {
        $this->actingAs($this->doctor->user)
            ->get('/agenda')
            ->assertOk()
            ->assertSee('Mon agenda')
            ->assertSee('Semaine du 2 au 7 novembre 2026 · 1 rendez-vous')
            ->assertSeeInOrder(['Lun', '2', 'Mar', '3', 'Mer', '4', 'Jeu', '5', 'Ven', '6', 'Sam', '7'])
            ->assertSee('K. Trabelsi')
            ->assertSee('Gérer mes disponibilités');
    }

    public function test_the_day_view_and_navigation(): void
    {
        $this->actingAs($this->doctor->user)
            ->get('/agenda?vue=jour&date=2026-11-05')
            ->assertOk()
            ->assertSee('Jeudi 5 novembre 2026 · 1 rendez-vous')
            ->assertSee('K. Trabelsi')
            ->assertSee('date=2026-11-04', false)
            ->assertSee('date=2026-11-06', false);

        // Le samedi suivant mène au lundi (le cabinet est fermé le dimanche).
        $this->actingAs($this->doctor->user)
            ->get('/agenda?vue=jour&date=2026-11-07')
            ->assertSee('date=2026-11-09', false);
    }

    public function test_periods_without_consultation_are_labelled(): void
    {
        $this->doctor->unavailabilities()->create([
            'starts_at' => now()->setDate(2026, 11, 6)->setTime(8, 0), 'ends_at' => now()->setDate(2026, 11, 6)->setTime(12, 0), 'reason' => 'Congé',
        ]);

        $this->actingAs($this->doctor->user)
            ->get('/agenda')
            ->assertSee('Indisponible · Congé')
            ->assertSee('Cabinet fermé'); // samedi (cabinet fermé par défaut)
    }

    public function test_a_doctor_only_sees_their_own_agenda(): void
    {
        $other = Doctor::factory()->withUser(['first_name' => 'Mehdi', 'last_name' => 'Saidi'])->create();

        $this->actingAs($other->user)
            ->get('/agenda?medecin='.$this->doctor->id)
            ->assertOk()
            ->assertDontSee('K. Trabelsi');
    }

    public function test_the_secretary_chooses_the_doctor(): void
    {
        Setting::create(['saturday_opening_time' => '08:00', 'saturday_closing_time' => '12:00']);
        Doctor::factory()->withUser(['first_name' => 'Leila', 'last_name' => 'Ammar'])->create();

        $this->actingAs($this->secretary)
            ->get('/agenda?medecin='.$this->doctor->id)
            ->assertOk()
            ->assertSee('Agenda de Dr Salma Ben Ali')
            ->assertSee('Dr Leila Ammar')
            ->assertSee('K. Trabelsi')
            ->assertSee('Nouveau rendez-vous');
    }

    public function test_the_admin_and_patients_have_no_agenda(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/agenda')->assertForbidden();
        $this->actingAs($this->patient->user)->get('/agenda')->assertForbidden();
    }
}
