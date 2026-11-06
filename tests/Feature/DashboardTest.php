<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page d'arrivée de chaque rôle après la connexion.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_secretary_dashboard_greets_her_and_shows_her_role(): void
    {
        $secretary = User::factory()->secretary()->create(['first_name' => 'Nadia', 'last_name' => 'Jlassi']);

        $this->actingAs($secretary)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour Nadia')
            ->assertSee('Secrétaire');
    }

    public function test_each_role_lands_on_its_own_page(): void
    {
        $this->actingAs(User::factory()->secretary()->create())->get('/dashboard')->assertOk();
        $this->actingAs(User::factory()->patient()->create())->get('/dashboard')->assertOk();

        // Le médecin arrive sur son agenda (maquette 11).
        $this->actingAs(User::factory()->doctor()->create())->get('/dashboard')->assertRedirect(route('agenda'));

        // L'administrateur arrive directement sur la gestion des comptes (maquette 16).
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect(route('admin.users.index'));
    }
}
