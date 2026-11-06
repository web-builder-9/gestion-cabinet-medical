<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * RG-03 : l'administrateur crée et modifie les comptes du personnel.
 */
class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['first_name' => 'Ridha', 'last_name' => 'Ferjani']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Ayari',
            'first_name' => 'Ines',
            'email' => 'ines@cabinet.test',
            'phone' => '22 123 456',
            'role' => 'secretary',
            'password' => 'motdepasse1',
            'password_confirmation' => 'motdepasse1',
        ], $overrides);
    }

    public function test_the_admin_sees_staff_accounts_but_not_patients(): void
    {
        User::factory()->secretary()->create(['first_name' => 'Nadia', 'last_name' => 'Jlassi']);
        User::factory()->doctor()->create(['first_name' => 'Salma', 'last_name' => 'Ben Ali']);
        User::factory()->patient()->create(['first_name' => 'Youssef', 'last_name' => 'Patient']);

        $this->actingAs($this->admin)
            ->get('/personnel')
            ->assertOk()
            ->assertSee('Nadia Jlassi')
            ->assertSee('Salma Ben Ali')
            ->assertSee('Ridha Ferjani')
            ->assertDontSee('Youssef Patient');
    }

    public function test_only_the_admin_can_open_account_management(): void
    {
        foreach ([User::factory()->doctor()->create(), User::factory()->secretary()->create(), User::factory()->patient()->create()] as $user) {
            $this->actingAs($user)->get('/personnel')->assertForbidden();
        }
    }

    public function test_the_admin_can_create_a_secretary_account(): void
    {
        $this->actingAs($this->admin)
            ->post('/personnel', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/personnel');

        $created = User::where('email', 'ines@cabinet.test')->firstOrFail();

        $this->assertSame(Role::Secretary, $created->role);
        $this->assertTrue(Hash::check('motdepasse1', $created->password));
        $this->assertNull($created->doctor);
    }

    public function test_a_doctor_account_needs_a_specialty_and_gets_a_doctor_profile(): void
    {
        $this->actingAs($this->admin)
            ->post('/personnel', $this->validData(['role' => 'doctor']))
            ->assertSessionHasErrors(['specialty_id' => 'Choisissez la spécialité du médecin.']);

        $cardiology = Specialty::factory()->create(['name' => 'Cardiologie']);

        $this->actingAs($this->admin)
            ->post('/personnel', $this->validData(['role' => 'doctor', 'specialty_id' => $cardiology->id]))
            ->assertSessionHasNoErrors();

        $doctor = User::where('email', 'ines@cabinet.test')->firstOrFail()->doctor;
        $this->assertSame('Cardiologie', $doctor->specialty->name);
    }

    public function test_a_patient_account_cannot_be_created_here(): void
    {
        $this->actingAs($this->admin)
            ->post('/personnel', $this->validData(['role' => 'patient']))
            ->assertSessionHasErrors(['role' => 'La valeur choisie pour rôle n\'est pas valide.']);

        $this->assertDatabaseMissing('users', ['email' => 'ines@cabinet.test']);
    }

    public function test_an_email_cannot_be_used_twice(): void
    {
        $this->actingAs($this->admin)
            ->post('/personnel', $this->validData(['email' => $this->admin->email]))
            ->assertSessionHasErrors(['email' => 'La valeur du champ adresse e-mail est déjà utilisée.']);
    }

    public function test_the_admin_can_change_another_member_role(): void
    {
        $secretary = User::factory()->secretary()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($this->admin)
            ->put("/personnel/{$secretary->id}", [
                'last_name' => $secretary->last_name,
                'first_name' => $secretary->first_name,
                'email' => $secretary->email,
                'role' => 'doctor',
                'specialty_id' => $specialty->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/personnel');

        $secretary->refresh();
        $this->assertSame(Role::Doctor, $secretary->role);
        $this->assertSame($specialty->id, $secretary->doctor->specialty_id);
    }

    public function test_the_admin_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->admin)
            ->put("/personnel/{$this->admin->id}", [
                'last_name' => 'Ferjani', 'first_name' => 'Ridha', 'email' => $this->admin->email, 'role' => 'secretary',
            ])
            ->assertSessionHasErrors(['role' => 'Vous ne pouvez pas modifier votre propre rôle.']);

        $this->assertSame(Role::Admin, $this->admin->fresh()->role);
    }

    public function test_the_admin_can_edit_their_own_details(): void
    {
        $this->actingAs($this->admin)
            ->get("/personnel/{$this->admin->id}/edit")
            ->assertOk()
            ->assertSee('vous ne pouvez pas modifier votre propre rôle');

        $this->actingAs($this->admin)
            ->put("/personnel/{$this->admin->id}", [
                'last_name' => 'Ferjani', 'first_name' => 'Ridha Ali', 'email' => $this->admin->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Ridha Ali', $this->admin->fresh()->first_name);
    }

    public function test_an_empty_password_keeps_the_current_one(): void
    {
        $secretary = User::factory()->secretary()->create();
        $oldHash = $secretary->password;

        $this->actingAs($this->admin)->put("/personnel/{$secretary->id}", [
            'last_name' => 'Nouveau', 'first_name' => 'Nom', 'email' => $secretary->email, 'role' => 'secretary',
            'password' => '', 'password_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame($oldHash, $secretary->fresh()->password);
    }

    public function test_patient_accounts_cannot_be_edited_here(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($this->admin)->get("/personnel/{$patient->id}/edit")->assertForbidden();
    }
}
