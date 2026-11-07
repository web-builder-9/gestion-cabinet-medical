<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BF-05 : chacun consulte et modifie son profil.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee($user->last_name);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'last_name' => 'Ben Salah',
                'first_name' => 'Amira',
                'phone' => '98451220',
                'email' => 'test@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Amira Ben Salah', $user->full_name);
        $this->assertSame('98 451 220', $user->phone);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'last_name' => $user->last_name,
                'first_name' => $user->first_name,
                'phone' => $user->phone,
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_a_patient_must_keep_a_unique_phone_number(): void
    {
        $other = User::factory()->create(['phone' => '22 123 456']);
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)
            ->patch('/profile', [
                'last_name' => 'Test', 'first_name' => 'Test', 'email' => $patient->email, 'phone' => '',
            ])
            ->assertSessionHasErrors(['phone' => 'Le champ téléphone est obligatoire.']);

        $this->actingAs($patient)
            ->patch('/profile', [
                'last_name' => 'Test', 'first_name' => 'Test', 'email' => $patient->email, 'phone' => $other->phone,
            ])
            ->assertSessionHasErrors(['phone' => 'La valeur du champ téléphone est déjà utilisée.']);
    }

    public function test_staff_phone_is_optional(): void
    {
        $secretary = User::factory()->secretary()->create();

        $this->actingAs($secretary)
            ->patch('/profile', [
                'last_name' => 'Jlassi', 'first_name' => 'Nadia', 'email' => $secretary->email, 'phone' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($secretary->refresh()->phone);
    }
}
