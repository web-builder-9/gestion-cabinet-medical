<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comptes et rôles : RG-01 (un seul rôle), RG-02 (compte désactivé), RG-04 (mot de passe).
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_activation_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'last_name' => 'Test',
            'first_name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password1',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $user->refresh();
        $this->assertSame(Role::Patient, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_role_helpers_and_scopes(): void
    {
        $doctor = User::factory()->doctor()->create();
        $secretary = User::factory()->secretary()->create();
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        $this->assertTrue($doctor->isDoctor());
        $this->assertTrue($doctor->isCareStaff());
        $this->assertTrue($secretary->isCareStaff());
        $this->assertFalse($admin->isCareStaff());
        $this->assertTrue($secretary->hasRole(Role::Doctor, Role::Secretary));
        $this->assertFalse($patient->hasRole(Role::Doctor, Role::Secretary));

        $this->assertEqualsCanonicalizing(
            [$doctor->id, $secretary->id, $admin->id],
            User::staff()->pluck('id')->all()
        );
    }

    public function test_a_doctor_account_has_a_doctor_profile_with_a_specialty(): void
    {
        $user = User::factory()->doctor()->create(['first_name' => 'Salma', 'last_name' => 'Ben Ali']);

        $this->assertInstanceOf(Doctor::class, $user->doctor);
        $this->assertNotNull($user->doctor->specialty);
        $this->assertSame('Dr Salma Ben Ali', $user->doctor->display_name);
    }

    public function test_an_inactive_account_cannot_log_in(): void
    {
        $user = User::factory()->secretary()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);

        $this->assertGuest();
    }

    public function test_an_account_deactivated_during_its_session_is_logged_out(): void
    {
        $user = User::factory()->secretary()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_passwords_need_eight_characters_with_a_letter_and_a_digit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'Le champ mot de passe doit contenir au moins un chiffre.']);

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'abc123',
                'password_confirmation' => 'abc123',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'Le champ mot de passe doit contenir au moins 8 caractères.']);
    }

    public function test_phone_numbers_are_normalized(): void
    {
        $user = User::factory()->create(['phone' => '+216 22-12-34-56']);

        $this->assertSame('22 123 456', $user->phone);
        $this->assertSame('22 123 457', User::factory()->create(['phone' => '22123457'])->phone);
    }
}
