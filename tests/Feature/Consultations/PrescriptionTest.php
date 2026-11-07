<?php

namespace Tests\Feature\Consultations;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Ordonnance imprimable et PDF (BF-22, maquette 15) ; comptes rendus du patient (BF-11, RG-21).
 */
class PrescriptionTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
        Setting::create(['address' => 'Avenue Habib Bourguiba, Midoun, Djerba', 'phone' => '75 600 600']);

        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:30'))->completed()->create();
        $this->consultation = Consultation::factory()->for($appointment)->create([
            'consulted_at' => $this->thursday('10:40'), 'diagnosis' => 'Bronchite aiguë', 'treatment' => 'Éviter la fumée.',
        ]);
        $this->consultation->prescriptionItems()->createMany([
            ['position' => 1, 'medication' => 'Salbutamol inhalé', 'dosage' => '100 µg', 'posology' => '2 bouffées si gêne', 'duration' => '10 jours'],
            ['position' => 2, 'medication' => 'Sirop antitussif', 'posology' => '1 cuillère le soir', 'duration' => '5 jours'],
        ]);
    }

    public function test_the_doctor_previews_the_prescription(): void
    {
        $this->actingAs($this->doctor->user)
            ->get("/consultations/{$this->consultation->id}/ordonnance")
            ->assertOk()
            ->assertSee('Dr Salma Ben Ali')
            ->assertSee('Djerba, le 5 novembre 2026')
            ->assertSee('M. Hedi Mansouri')
            ->assertSeeInOrder(['Salbutamol inhalé 100 µg', '2 bouffées si gêne, pendant 10 jours.', 'Sirop antitussif', '1 cuillère le soir, pendant 5 jours.'])
            ->assertSee('Éviter la fumée.')
            ->assertSee('Ordonnance n° '.$this->consultation->prescriptionNumber())
            ->assertSee('Télécharger le PDF');
    }

    public function test_the_prescription_is_downloaded_as_a_pdf(): void
    {
        $response = $this->actingAs($this->doctor->user)->get("/consultations/{$this->consultation->id}/ordonnance/pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($this->consultation->prescriptionNumber().'.pdf', $response->headers->get('content-disposition'));
    }

    public function test_the_patient_downloads_their_own_prescription_only(): void
    {
        $this->actingAs($this->patient->user)->get("/consultations/{$this->consultation->id}/ordonnance/pdf")->assertOk();

        $stranger = Patient::factory()->create();
        $this->actingAs($stranger->user)->get("/consultations/{$this->consultation->id}/ordonnance/pdf")->assertForbidden();
    }

    public function test_a_consultation_without_medication_has_no_prescription(): void
    {
        $this->consultation->prescriptionItems()->delete();

        $this->actingAs($this->doctor->user)->get("/consultations/{$this->consultation->id}/ordonnance")->assertNotFound();
    }

    public function test_the_patient_reads_their_reports(): void
    {
        $this->actingAs($this->patient->user)
            ->get('/mes-comptes-rendus')
            ->assertOk()
            ->assertSee('Jeudi 5 novembre 2026')
            ->assertSee('Bronchite aiguë')
            ->assertSee('Salbutamol inhalé 100 µg')
            ->assertSee('Télécharger l\'ordonnance (PDF)', false);

        $this->actingAs($this->patient->user)
            ->get('/dashboard')
            ->assertSee('Voir tous mes comptes rendus')
            ->assertSee(route('consultations.prescription.pdf', $this->consultation));

        // Le patient n'ouvre pas la page médecin de la consultation.
        $this->actingAs($this->patient->user)->get("/consultations/{$this->consultation->id}")->assertForbidden();
    }
}
