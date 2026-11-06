<?php

namespace Tests\Feature\PatientSpace;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * « Mes rendez-vous » (maquette 6, BF-10, BF-11).
 */
class PatientDashboardTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
    }

    public function test_the_patient_sees_upcoming_appointments_history_and_last_report(): void
    {
        Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:30'))->pending()->create(['reason' => 'Contrôle après bronchite']);
        $past = Appointment::factory()->for($this->patient)->for($this->doctor)->at(now()->subDays(20)->setTime(9, 30))->completed()->create(['reason' => 'Toux persistante']);
        Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('11:00'))->cancelled()->create(['reason' => 'Contrôle asthme']);
        Consultation::factory()->for($past)->create(['consulted_at' => $past->starts_at, 'diagnosis' => 'Bronchite aiguë', 'treatment' => 'Sirop antitussif 5 jours']);
        // Le rendez-vous d'un autre patient n'apparaît pas.
        Appointment::factory()->for(Patient::factory()->withUser(['last_name' => 'Autre']))->for($this->doctor)->at($this->thursday('09:00'))->confirmed()->create(['reason' => 'Secret']);

        $this->actingAs($this->patient->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour Hedi')
            ->assertSeeInOrder(['À venir', '10:30 – 11:00 · Dr Salma Ben Ali', 'Contrôle après bronchite', 'En attente', 'Modifier', 'Annuler'])
            ->assertSeeInOrder(['Historique', 'Contrôle asthme', 'Annulé', 'Toux persistante', 'Terminé'])
            ->assertSeeInOrder(['Dernier compte rendu', 'Bronchite aiguë', 'Sirop antitussif 5 jours'])
            ->assertDontSee('Secret');
    }

    public function test_the_patient_menu(): void
    {
        $this->actingAs($this->patient->user)
            ->get('/dashboard')
            ->assertSee('Espace patient · Hedi Mansouri')
            ->assertSee('Prendre rendez-vous')
            ->assertSee('Mes rendez-vous');
    }
}
