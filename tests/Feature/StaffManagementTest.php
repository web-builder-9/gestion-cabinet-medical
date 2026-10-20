<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->doctor()->create(['name' => 'Dr Sami']);
    }

    public function test_the_doctor_sees_the_staff_list_without_patient_accounts(): void
    {
        User::factory()->secretary()->create(['name' => 'Amira Secrétaire']);
        User::factory()->patient()->create(['name' => 'Youssef Patient']);

        $this->actingAs($this->doctor)
            ->get('/personnel')
            ->assertOk()
            ->assertSee('Dr Sami')
            ->assertSee('Amira Secrétaire')
            ->assertDontSee('Youssef Patient');
    }

    public function test_only_the_doctor_can_open_staff_management(): void
    {
        $this->actingAs(User::factory()->secretary()->create())->get('/personnel')->assertForbidden();
        $this->actingAs(User::factory()->patient()->create())->get('/personnel')->assertForbidden();
    }

    public function test_the_staff_link_appears_for_the_doctor_only(): void
    {
        $this->actingAs($this->doctor)->get('/dashboard')->assertSee(route('staff.index'));

        $this->actingAs(User::factory()->secretary()->create())
            ->get('/dashboard')
            ->assertDontSee(route('staff.index'));
    }

    public function test_the_doctor_can_create_a_secretary_account(): void
    {
        $this->actingAs($this->doctor)
            ->post('/personnel', [
                'name' => 'Ines Ayari',
                'email' => 'ines@cabinet.test',
                'phone' => '22 123 456',
                'role' => 'secretary',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/personnel');

        $created = User::where('email', 'ines@cabinet.test')->firstOrFail();

        $this->assertSame(Role::Secretary, $created->role);
        $this->assertTrue(Hash::check('motdepasse', $created->password));
    }

    public function test_a_patient_account_cannot_be_created_here(): void
    {
        $this->actingAs($this->doctor)
            ->post('/personnel', [
                'name' => 'Faux Patient',
                'email' => 'faux@cabinet.test',
                'role' => 'patient',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
            ])
            ->assertSessionHasErrors(['role' => 'La valeur choisie pour rôle n\'est pas valide.']);

        $this->assertDatabaseMissing('users', ['email' => 'faux@cabinet.test']);
    }

    public function test_an_email_cannot_be_used_twice(): void
    {
        $this->actingAs($this->doctor)
            ->post('/personnel', [
                'name' => 'Doublon',
                'email' => $this->doctor->email,
                'role' => 'secretary',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
            ])
            ->assertSessionHasErrors(['email' => 'La valeur du champ adresse e-mail est déjà utilisée.']);
    }

    public function test_the_doctor_can_change_another_member_role(): void
    {
        $secretary = User::factory()->secretary()->create();

        $this->actingAs($this->doctor)
            ->put("/personnel/{$secretary->id}", [
                'name' => $secretary->name,
                'email' => $secretary->email,
                'role' => 'doctor',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/personnel');

        $this->assertSame(Role::Doctor, $secretary->fresh()->role);
    }

    public function test_the_doctor_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->doctor)
            ->put("/personnel/{$this->doctor->id}", [
                'name' => 'Dr Sami',
                'email' => $this->doctor->email,
                'role' => 'secretary',
            ])
            ->assertSessionHasErrors(['role' => 'Vous ne pouvez pas modifier votre propre rôle.']);

        $this->assertSame(Role::Doctor, $this->doctor->fresh()->role);
    }

    public function test_the_doctor_can_edit_their_own_details(): void
    {
        $this->actingAs($this->doctor)
            ->get("/personnel/{$this->doctor->id}/edit")
            ->assertOk()
            ->assertSee('vous ne pouvez pas modifier votre propre rôle');

        $this->actingAs($this->doctor)
            ->put("/personnel/{$this->doctor->id}", [
                'name' => 'Dr Sami Ben Salah',
                'email' => $this->doctor->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Dr Sami Ben Salah', $this->doctor->fresh()->name);
        $this->assertSame(Role::Doctor, $this->doctor->fresh()->role);
    }

    public function test_an_empty_password_keeps_the_current_one(): void
    {
        $secretary = User::factory()->secretary()->create();
        $oldHash = $secretary->password;

        $this->actingAs($this->doctor)->put("/personnel/{$secretary->id}", [
            'name' => 'Nouveau nom',
            'email' => $secretary->email,
            'role' => 'secretary',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame($oldHash, $secretary->fresh()->password);
    }

    public function test_patient_accounts_cannot_be_edited_here(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($this->doctor)->get("/personnel/{$patient->id}/edit")->assertForbidden();
    }
}
