<?php

namespace Tests\Feature\Patients;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFormTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretary = User::factory()->secretary()->create();
    }

    /**
     * Données valides, à surcharger dans chaque test.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amira',
            'last_name' => 'Trabelsi',
            'cin' => '01234567',
            'birth_date' => '1990-05-14',
            'gender' => 'F',
            'phone' => '22123456',
            'email' => 'amira@example.com',
            'address' => '12, rue de Tunis, Midoun',
        ], $overrides);
    }

    public function test_the_creation_form_is_displayed(): void
    {
        $this->actingAs($this->secretary)
            ->get('/patients/create')
            ->assertOk()
            ->assertSee('Nouveau patient')
            ->assertSee('Date de naissance');
    }

    public function test_the_secretary_can_add_a_patient_with_an_empty_medical_record(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $patient = Patient::where('cin', '01234567')->firstOrFail();

        $this->assertSame('Amira Trabelsi', $patient->full_name);
        $this->assertSame(Gender::Female, $patient->gender);
        $this->assertNotNull($patient->medicalRecord, 'Le dossier médical doit être créé avec la fiche.');
        $this->assertNull($patient->medicalRecord->allergies);
    }

    public function test_the_phone_number_is_stored_in_a_single_format(): void
    {
        foreach (['22123456', '22 12 34 56', '+216 22 123 456', '22-123-456'] as $i => $typed) {
            $this->actingAs($this->secretary)
                ->post('/patients', $this->validData(['cin' => "0000000{$i}", 'phone' => $typed]))
                ->assertSessionHasNoErrors();

            $this->assertSame('22 123 456', Patient::where('cin', "0000000{$i}")->value('phone'), "Saisie : {$typed}");
        }
    }

    public function test_required_fields_are_checked_in_french(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', [])
            ->assertSessionHasErrors([
                'last_name' => 'Le champ nom de famille est obligatoire.',
                'first_name' => 'Le champ prénom est obligatoire.',
                'birth_date' => 'Le champ date de naissance est obligatoire.',
                'gender' => 'Le champ sexe est obligatoire.',
                'phone' => 'Le champ téléphone est obligatoire.',
            ]);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData([
                'cin' => '123',
                'birth_date' => now()->addDay()->toDateString(),
                'phone' => '123',
                'gender' => 'X',
            ]))
            ->assertSessionHasErrors([
                'cin' => 'Le champ CIN doit contenir 8 chiffres.',
                'birth_date' => 'La date de naissance ne peut pas être dans le futur.',
                'phone' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 22 123 456).',
                'gender' => 'La valeur choisie pour sexe n\'est pas valide.',
            ]);

        $this->assertSame(0, Patient::count());
    }

    public function test_a_cin_cannot_be_used_twice_even_by_an_archived_patient(): void
    {
        Patient::factory()->create(['cin' => '01234567'])->delete();

        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData())
            ->assertSessionHasErrors(['cin' => 'La valeur du champ CIN est déjà utilisée.']);
    }

    public function test_the_cin_is_optional(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData(['cin' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Patient::firstOrFail()->cin);
    }

    public function test_a_patient_can_be_updated_and_keeps_their_own_cin(): void
    {
        $patient = Patient::factory()->create(['cin' => '01234567']);

        $this->actingAs($this->secretary)
            ->get("/patients/{$patient->id}/edit")
            ->assertOk()
            ->assertSee('01234567');

        $this->actingAs($this->secretary)
            ->put("/patients/{$patient->id}", $this->validData(['first_name' => 'Amina']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('Amina', $patient->fresh()->first_name);
    }

    public function test_a_patient_account_cannot_create_or_edit_patients(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($patientUser)->get('/patients/create')->assertForbidden();
        $this->actingAs($patientUser)->post('/patients', $this->validData())->assertForbidden();
        $this->actingAs($patientUser)->put("/patients/{$patient->id}", $this->validData())->assertForbidden();
    }
}
