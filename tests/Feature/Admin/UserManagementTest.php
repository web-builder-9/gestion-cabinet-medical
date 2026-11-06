<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Comptes du personnel (maquette 16) : RG-01, RG-02, RG-03, RG-06 et RG-08.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
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
            'phone' => '22123456',
            'role' => 'secretary',
        ], $overrides);
    }

    public function test_the_admin_sees_staff_accounts_but_not_patients(): void
    {
        User::factory()->secretary()->create(['first_name' => 'Nadia', 'last_name' => 'Jlassi']);
        Doctor::factory()->withUser(['first_name' => 'Salma', 'last_name' => 'Ben Ali'])
            ->for(Specialty::factory()->state(['name' => 'Pédiatrie']))->create();
        User::factory()->patient()->create(['first_name' => 'Youssef', 'last_name' => 'Patient']);

        $this->actingAs($this->admin)
            ->get('/admin/utilisateurs')
            ->assertOk()
            ->assertSee('Nadia Jlassi')
            ->assertSee('Dr Salma Ben Ali')
            ->assertSee('Pédiatrie')
            ->assertSee('Ridha Ferjani')
            ->assertSee('Vous')
            ->assertDontSee('Youssef Patient');
    }

    public function test_the_role_tabs_filter_the_list(): void
    {
        User::factory()->secretary()->create(['first_name' => 'Nadia', 'last_name' => 'Jlassi']);
        Doctor::factory()->withUser(['first_name' => 'Salma', 'last_name' => 'Ben Ali'])->create();

        $this->actingAs($this->admin)
            ->get('/admin/utilisateurs?role=doctor')
            ->assertOk()
            ->assertSee('Dr Salma Ben Ali')
            ->assertDontSee('Nadia Jlassi');
    }

    public function test_only_the_admin_can_open_account_management(): void
    {
        $others = [
            User::factory()->doctor()->create(),
            User::factory()->secretary()->create(),
            User::factory()->patient()->create(),
        ];

        foreach ($others as $user) {
            $this->actingAs($user)->get('/admin/utilisateurs')->assertForbidden();
            $this->actingAs($user)->post('/admin/utilisateurs', $this->validData())->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'ines@cabinet.test']);
    }

    public function test_the_admin_dashboard_leads_to_account_management(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect('/admin/utilisateurs');
    }

    public function test_a_created_account_receives_an_invitation_instead_of_a_password(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/utilisateurs', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/utilisateurs');

        $created = User::where('email', 'ines@cabinet.test')->firstOrFail();

        $this->assertSame(Role::Secretary, $created->role);
        $this->assertSame('22 123 456', $created->phone);
        $this->assertTrue($created->is_active);
        $this->assertNull($created->doctor);

        // Le lien de l'invitation ouvre le formulaire « choisir son mot de passe ».
        auth()->logout();
        Notification::assertSentTo($created, AccountInvitation::class, function (AccountInvitation $invitation) use ($created) {
            $this->get($invitation->url($created))->assertOk();

            return true;
        });
    }

    public function test_the_invited_person_chooses_their_password(): void
    {
        $this->actingAs($this->admin)->post('/admin/utilisateurs', $this->validData());
        $created = User::where('email', 'ines@cabinet.test')->firstOrFail();
        auth()->logout();

        Notification::assertSentTo($created, AccountInvitation::class, function (AccountInvitation $invitation) use ($created) {
            $this->post('/reset-password', [
                'token' => $invitation->token,
                'email' => $created->email,
                'password' => 'Bienvenue2026',
                'password_confirmation' => 'Bienvenue2026',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->post('/login', ['email' => 'ines@cabinet.test', 'password' => 'Bienvenue2026']);
        $this->assertAuthenticatedAs($created);
    }

    public function test_a_doctor_account_needs_a_specialty_and_gets_a_doctor_profile(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/utilisateurs', $this->validData(['role' => 'doctor']))
            ->assertSessionHasErrors(['specialty_id' => 'Choisissez la spécialité du médecin.']);

        $specialty = Specialty::factory()->create(['name' => 'Dermatologie']);

        $this->actingAs($this->admin)
            ->post('/admin/utilisateurs', $this->validData(['role' => 'doctor', 'specialty_id' => $specialty->id]))
            ->assertSessionHasNoErrors();

        $created = User::where('email', 'ines@cabinet.test')->firstOrFail();
        $this->assertTrue($created->isDoctor());
        $this->assertTrue($created->doctor->specialty->is($specialty));
    }

    public function test_a_patient_account_cannot_be_created_here(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/utilisateurs', $this->validData(['role' => 'patient']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'ines@cabinet.test']);
    }

    public function test_an_email_cannot_be_used_twice(): void
    {
        User::factory()->create(['email' => 'ines@cabinet.test']);

        $this->actingAs($this->admin)
            ->post('/admin/utilisateurs', $this->validData())
            ->assertSessionHasErrors('email');
    }

    public function test_the_admin_can_change_another_member_role_and_specialty(): void
    {
        $member = User::factory()->secretary()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($this->admin)
            ->put("/admin/utilisateurs/{$member->id}", [
                ...$this->validData(['email' => $member->email]),
                'role' => 'doctor',
                'specialty_id' => $specialty->id,
                'presentation' => 'Santé de la peau.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/utilisateurs');

        $member->refresh();
        $this->assertSame(Role::Doctor, $member->role);
        $this->assertSame('Santé de la peau.', $member->doctor->presentation);
        $this->assertTrue($member->doctor->specialty->is($specialty));
    }

    public function test_the_admin_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->admin)
            ->put("/admin/utilisateurs/{$this->admin->id}", $this->validData(['role' => 'secretary']))
            ->assertSessionHasErrors(['role' => 'Vous ne pouvez pas modifier votre propre rôle.']);

        $this->assertSame(Role::Admin, $this->admin->fresh()->role);
    }

    public function test_the_admin_can_edit_their_own_details(): void
    {
        $this->actingAs($this->admin)
            ->get("/admin/utilisateurs/{$this->admin->id}/modifier")
            ->assertOk()
            ->assertSee('Vous ne pouvez pas modifier votre propre rôle.');

        $data = $this->validData(['email' => 'direction@cabinet.test']);
        unset($data['role']);

        $this->actingAs($this->admin)
            ->put("/admin/utilisateurs/{$this->admin->id}", $data)
            ->assertSessionHasNoErrors();

        $this->assertSame('direction@cabinet.test', $this->admin->fresh()->email);
    }

    public function test_patient_accounts_cannot_be_edited_here(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($this->admin)->get("/admin/utilisateurs/{$patient->id}/modifier")->assertForbidden();
        $this->actingAs($this->admin)->patch("/admin/utilisateurs/{$patient->id}/desactiver")->assertForbidden();
    }

    public function test_a_deactivated_account_can_no_longer_sign_in_until_reactivated(): void
    {
        $member = User::factory()->secretary()->create(['email' => 'nadia@cabinet.test']);

        $this->actingAs($this->admin)
            ->patch("/admin/utilisateurs/{$member->id}/desactiver")
            ->assertRedirect('/admin/utilisateurs')
            ->assertSessionHas('status');
        $this->assertFalse($member->fresh()->is_active);

        auth()->logout();
        $this->post('/login', ['email' => 'nadia@cabinet.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->admin)->patch("/admin/utilisateurs/{$member->id}/reactiver");
        $this->assertTrue($member->fresh()->is_active);
    }

    public function test_the_admin_cannot_deactivate_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/utilisateurs/{$this->admin->id}/desactiver")
            ->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_a_doctor_with_upcoming_appointments_cannot_be_deactivated(): void
    {
        $doctor = Doctor::factory()->withUser(['first_name' => 'Mehdi', 'last_name' => 'Saidi'])->create();
        Appointment::factory()->for($doctor)->at(now()->addDays(2))->confirmed()->create();
        Appointment::factory()->for($doctor)->at(now()->addDays(3))->pending()->create();
        // Ne bloquent pas : rendez-vous passé, ou à venir mais annulé.
        Appointment::factory()->for($doctor)->at(now()->subDays(3))->completed()->create();
        Appointment::factory()->for($doctor)->at(now()->addDays(4))->cancelled()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/utilisateurs/{$doctor->user_id}/desactiver")
            ->assertRedirect('/admin/utilisateurs')
            ->assertSessionHas('error', "Dr Mehdi Saidi n'a pas été désactivé : il a 2 rendez-vous à venir. Le secrétariat doit d'abord les annuler ou les déplacer.");

        $this->assertTrue($doctor->user->fresh()->is_active);
    }

    public function test_a_doctor_without_upcoming_appointments_can_be_deactivated(): void
    {
        $doctor = Doctor::factory()->create();
        Appointment::factory()->for($doctor)->at(now()->subDays(3))->completed()->create();

        $this->actingAs($this->admin)->patch("/admin/utilisateurs/{$doctor->user_id}/desactiver");

        $this->assertFalse($doctor->user->fresh()->is_active);
    }

    public function test_the_invitation_can_be_sent_again(): void
    {
        $member = User::factory()->secretary()->create();

        $this->actingAs($this->admin)
            ->post("/admin/utilisateurs/{$member->id}/invitation")
            ->assertSessionHas('status');

        Notification::assertSentTo($member, AccountInvitation::class);
    }
}
