<?php

namespace Tests\Feature\Patients;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * BF-12 : création et modification d'un dossier patient ; RG-05 (téléphone unique).
 */
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
            'data_consent' => '1',
        ], $overrides);
    }

    public function test_the_creation_form_is_displayed(): void
    {
        $this->actingAs($this->secretary)
            ->get('/patients/create')
            ->assertOk()
            ->assertSee('Nouveau dossier patient')
            ->assertSee('loi organique n° 2004-63');
    }

    public function test_the_secretary_creates_an_account_a_patient_file_and_an_empty_medical_record(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $patient = Patient::where('cin', '01234567')->firstOrFail();

        $this->assertSame('Amira Trabelsi', $patient->full_name);
        $this->assertSame(Role::Patient, $patient->user->role);
        $this->assertSame('22 123 456', $patient->user->phone);
        $this->assertSame(Gender::Female, $patient->gender);
        $this->assertTrue($patient->data_consent);
        $this->assertNotNull($patient->consent_given_at);
        $this->assertNotNull($patient->medicalRecord, 'Le dossier médical doit être créé avec la fiche.');

        // Personne ne connaît le mot de passe aléatoire : pas d'accès en ligne tant qu'il n'est pas choisi.
        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => 'amira@example.com', 'password' => 'password']));
    }

    public function test_the_patient_consent_is_required(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData(['data_consent' => null]))
            ->assertSessionHasErrors(['data_consent' => 'L\'accord du patient pour le traitement de ses données est obligatoire.']);

        $this->assertSame(0, Patient::count());
    }

    public function test_the_phone_number_is_stored_in_a_single_format(): void
    {
        foreach (['22 12 34 56', '+216 23 123 456', '24-123-456'] as $i => $typed) {
            $this->actingAs($this->secretary)
                ->post('/patients', $this->validData(['cin' => "0000000{$i}", 'email' => "p{$i}@example.com", 'phone' => $typed]))
                ->assertSessionHasNoErrors();
        }

        $this->assertEqualsCanonicalizing(
            ['22 123 456', '23 123 456', '24 123 456'],
            User::withRole(Role::Patient)->pluck('phone')->all()
        );
    }

    public function test_a_phone_number_cannot_belong_to_two_patients(): void
    {
        Patient::factory()->withUser(['phone' => '22 123 456'])->create();

        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData())
            ->assertSessionHasErrors(['phone' => 'Ce numéro appartient déjà à un autre patient : vérifiez qu\'il n\'a pas déjà un dossier.']);
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

    public function test_cin_and_email_are_optional(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', $this->validData(['cin' => '', 'email' => '']))
            ->assertSessionHasNoErrors();

        $patient = Patient::firstOrFail();
        $this->assertNull($patient->cin);
        $this->assertNull($patient->user->email);
        $this->assertFalse($patient->hasPortalAccess());
    }

    public function test_a_patient_can_be_updated_and_keeps_their_own_phone_and_cin(): void
    {
        $patient = Patient::factory()->withUser(['phone' => '22 123 456', 'email' => 'amira@example.com'])->create(['cin' => '01234567']);

        $this->actingAs($this->secretary)
            ->get("/patients/{$patient->id}/edit")
            ->assertOk()
            ->assertSee('01234567');

        $this->actingAs($this->secretary)
            ->put("/patients/{$patient->id}", $this->validData(['first_name' => 'Amina', 'data_consent' => null]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('patients.show', $patient));

        $this->assertSame('Amina', $patient->user->fresh()->first_name);
    }

    public function test_a_patient_or_an_admin_cannot_create_or_edit_patient_files(): void
    {
        $patient = Patient::factory()->create();

        foreach ([User::factory()->patient()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/patients/create')->assertForbidden();
            $this->actingAs($user)->post('/patients', $this->validData())->assertForbidden();
            $this->actingAs($user)->put("/patients/{$patient->id}", $this->validData())->assertForbidden();
        }
    }
}
