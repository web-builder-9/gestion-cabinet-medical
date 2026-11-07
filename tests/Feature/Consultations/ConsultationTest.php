<?php

namespace Tests\Feature\Consultations;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Consultations (BF-20, BF-21, maquette 14) : RG-18 à RG-22.
 */
class ConsultationTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
        $this->patient->medicalRecord->update(['allergies' => 'pénicilline', 'medical_history' => 'Asthme léger depuis l\'enfance']);
        $this->appointment = Appointment::factory()->for($this->patient)->for($this->doctor)
            ->at($this->thursday('10:30'))->confirmed()->create(['reason' => 'Contrôle après bronchite']);
        $this->travelTo($this->thursday('10:35'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function report(array $overrides = []): array
    {
        return array_merge([
            'reason' => 'Contrôle après bronchite, toux résiduelle',
            'observations' => 'Toux sèche nocturne, pas de fièvre.',
            'diagnosis' => 'Toux post-infectieuse',
            'treatment' => 'Éviter la fumée.',
            'items' => [
                ['medication' => 'Salbutamol inhalé', 'dosage' => '100 µg', 'posology' => '2 bouffées si gêne', 'duration' => '10 jours'],
                ['medication' => '', 'dosage' => '', 'posology' => '', 'duration' => ''], // ligne vide ignorée
                ['medication' => 'Sirop antitussif', 'dosage' => '', 'posology' => '1 cuillère le soir', 'duration' => '5 jours'],
            ],
        ], $overrides);
    }

    public function test_the_form_shows_the_patient_allergies_and_history(): void
    {
        $previous = Consultation::factory()->for(Appointment::factory()->for($this->patient)->at(now()->subMonth())->completed())
            ->create(['diagnosis' => 'Bronchite aiguë']);

        $this->actingAs($this->doctor->user)
            ->get("/rendez-vous/{$this->appointment->id}/consultation")
            ->assertOk()
            ->assertSee('Consultation du 5 novembre 2026')
            ->assertSee('il passera à « Terminé » à l\'enregistrement')
            ->assertSee('Allergie : pénicilline')
            ->assertSee('allergique : pénicilline')
            ->assertSee('Bronchite aiguë')
            ->assertSee('value="Contrôle après bronchite"', false);
    }

    public function test_the_doctor_records_the_consultation_and_the_appointment_is_completed(): void
    {
        $this->actingAs($this->doctor->user)
            ->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report())
            ->assertSessionHasNoErrors();

        $consultation = $this->appointment->consultation()->sole();
        $this->assertSame('Toux post-infectieuse', $consultation->diagnosis);
        $this->assertSame(['Salbutamol inhalé', 'Sirop antitussif'], $consultation->prescriptionItems->pluck('medication')->all());
        $this->assertSame([1, 2], $consultation->prescriptionItems->pluck('position')->all());

        // RG-18 : le rendez-vous passe à « Terminé », changement historisé (RG-23).
        $this->assertSame(AppointmentStatus::Completed, $this->appointment->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $this->appointment->statusChanges->last()->new_status);
    }

    public function test_save_and_print_leads_to_the_prescription(): void
    {
        $this->actingAs($this->doctor->user)
            ->post("/rendez-vous/{$this->appointment->id}/consultation", [...$this->report(), 'next' => 'prescription'])
            ->assertRedirect(route('consultations.prescription', $this->appointment->consultation()->sole()));
    }

    public function test_a_started_line_must_name_the_medication(): void
    {
        $this->actingAs($this->doctor->user)
            ->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report([
                'items' => [['medication' => '', 'dosage' => '500 mg', 'posology' => '', 'duration' => '']],
            ]))
            ->assertSessionHasErrors([
                'items.0.medication' => 'Indiquez le médicament de la ligne 1.',
                'items.0.posology' => 'Indiquez la posologie de la ligne 1.',
            ]);

        $this->assertSame(AppointmentStatus::Confirmed, $this->appointment->fresh()->status);
        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_one_consultation_per_appointment_by_its_doctor_once_confirmed(): void
    {
        // Un autre médecin ne peut pas enregistrer la consultation de ce rendez-vous.
        $other = Doctor::factory()->create();
        $this->actingAs($other->user)->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report())->assertForbidden();

        // Une demande encore en attente doit d'abord être confirmée (RG-17).
        $pending = Appointment::factory()->for($this->doctor)->at($this->thursday('11:00'))->pending()->create();
        $this->actingAs($this->doctor->user)->get("/rendez-vous/{$pending->id}/consultation")->assertForbidden();

        // Pas avant le jour du rendez-vous.
        $later = Appointment::factory()->for($this->doctor)->at(now()->addDays(2))->confirmed()->create();
        $this->actingAs($this->doctor->user)->get("/rendez-vous/{$later->id}/consultation")->assertForbidden();

        // Une seule consultation par rendez-vous (RG-19).
        $this->actingAs($this->doctor->user)->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report());
        $this->actingAs($this->doctor->user)->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report())->assertForbidden();
        $this->assertDatabaseCount('consultations', 1);
    }

    public function test_only_the_author_edits_the_report(): void
    {
        $this->actingAs($this->doctor->user)->post("/rendez-vous/{$this->appointment->id}/consultation", $this->report());
        $consultation = Consultation::sole();

        $this->actingAs($this->doctor->user)
            ->put("/consultations/{$consultation->id}", $this->report(['diagnosis' => 'Asthme', 'items' => [['medication' => 'Ventoline', 'posology' => '2 bouffées en cas de crise']]]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('consultations.show', $consultation));

        $this->assertSame('Asthme', $consultation->fresh()->diagnosis);
        $this->assertSame(['Ventoline'], $consultation->prescriptionItems()->pluck('medication')->all());

        // RG-20 : un autre médecin la lit, mais ne la modifie pas.
        $other = Doctor::factory()->create();
        $this->actingAs($other->user)->get("/consultations/{$consultation->id}")->assertOk()->assertSee('Lecture seule')->assertDontSee('/modifier');
        $this->actingAs($other->user)->put("/consultations/{$consultation->id}", $this->report())->assertForbidden();
    }

    public function test_the_secretary_and_the_admin_never_see_consultations(): void
    {
        $consultation = Consultation::factory()->for($this->appointment)->create(['diagnosis' => 'Diagnostic confidentiel']);

        foreach ([$this->secretary, User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get("/consultations/{$consultation->id}")->assertForbidden();
            $this->actingAs($user)->get("/consultations/{$consultation->id}/ordonnance")->assertForbidden();
            $this->actingAs($user)->get('/consultations')->assertForbidden();
        }

        // Ni sur la fiche patient ni sur la fiche du rendez-vous.
        $this->actingAs($this->secretary)->get("/patients/{$this->patient->id}")->assertOk()->assertDontSee('Diagnostic confidentiel');
        $this->actingAs($this->secretary)->get("/rendez-vous/{$this->appointment->id}")->assertOk()->assertDontSee('Compte rendu');
    }

    public function test_the_patient_page_lists_the_consultations_and_starts_today_s_one(): void
    {
        Consultation::factory()->for(Appointment::factory()->for($this->patient)->at(now()->subMonth())->completed())
            ->create(['reason' => 'Toux persistante', 'diagnosis' => 'Bronchite aiguë']);

        $this->actingAs($this->doctor->user)
            ->get("/patients/{$this->patient->id}")
            ->assertSee('Historique des consultations')
            ->assertSee('Toux persistante')
            ->assertSee('Bronchite aiguë')
            ->assertSee(route('consultations.create', $this->appointment));
    }

    public function test_the_doctor_consultations_page_lists_today_s_patients(): void
    {
        $this->actingAs($this->doctor->user)
            ->get('/consultations')
            ->assertOk()
            ->assertSee('Hedi Mansouri')
            ->assertSee(route('consultations.create', $this->appointment));
    }
}
