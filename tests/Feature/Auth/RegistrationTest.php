<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'inscription publique est désactivée : seuls le médecin (gestion du
 * personnel) et les seeders créent des comptes.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_registration_page_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_nobody_can_register_by_posting_the_form(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_the_login_page_has_no_registration_link(): void
    {
        $this->get('/login')->assertDontSee('/register');
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
