<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inscription d'un patient (BF-03, maquette 2) : RG-02, RG-03, RG-04, RG-05.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Ben Salah',
            'first_name' => 'Amira',
            'email' => 'amira.bs@exemple.tn',
            'phone' => '98451220',
            'birth_date' => '1992-03-14',
            'gender' => 'F',
            'password' => 'bronchite26',
            'password_confirmation' => 'bronchite26',
            'data_consent' => '1',
        ], $overrides);
    }

    public function test_the_registration_page_is_displayed(): void
    {
        $this->get('/inscription')
            ->assertOk()
            ->assertSee('Créer mon compte patient')
            ->assertSee('loi organique n° 2004-63');
    }

    public function test_a_visitor_creates_a_patient_account_with_a_file_and_a_medical_record(): void
    {
        $this->post('/inscription', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'amira.bs@exemple.tn')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Patient, $user->role); // RG-03 : un visiteur ne crée qu'un compte patient
        $this->assertSame('98 451 220', $user->phone);

        $patient = $user->patient;
        $this->assertNotNull($patient);
        $this->assertTrue($patient->data_consent);
        $this->assertNotNull($patient->medicalRecord);
    }

    public function test_a_visitor_cannot_choose_a_staff_role(): void
    {
        $this->post('/inscription', $this->validData(['role' => 'admin']));

        $this->assertSame(Role::Patient, User::sole()->role);
    }

    public function test_an_email_already_used_is_explained(): void
    {
        User::factory()->create(['email' => 'amira.bs@exemple.tn']);

        $this->post('/inscription', $this->validData())
            ->assertSessionHasErrors(['email' => 'Cette adresse e-mail a déjà un compte. Connectez-vous ou réinitialisez votre mot de passe.']);
    }

    public function test_a_phone_number_known_by_the_cabinet_is_explained(): void
    {
        Patient::factory()->withUser(['phone' => '98 451 220', 'email' => null])->create();

        $this->post('/inscription', $this->validData())
            ->assertSessionHasErrors(['phone' => 'Ce numéro correspond déjà à un dossier du cabinet : demandez au secrétariat de vous envoyer l\'accès à votre espace patient.']);
    }

    public function test_the_password_needs_8_characters_with_a_letter_and_a_digit(): void
    {
        foreach (['court1', 'motdepasse', '12345678'] as $password) {
            $this->post('/inscription', $this->validData(['password' => $password, 'password_confirmation' => $password]))
                ->assertSessionHasErrors(['password' => '8 caractères minimum, dont au moins une lettre et un chiffre.']);
        }

        $this->assertGuest();
    }

    public function test_the_consent_is_required_and_errors_are_summarised(): void
    {
        $this->followingRedirects()
            ->from('/inscription')
            ->post('/inscription', $this->validData(['data_consent' => null, 'email' => 'pas-une-adresse']))
            ->assertSee("Le compte n'a pas été créé.", false)
            ->assertSee('Corrigez les 2 champs signalés.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_login_page_links_to_the_registration(): void
    {
        $this->get('/login')->assertSee(route('register'));
    }

    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
