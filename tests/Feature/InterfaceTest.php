<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BNF-07 (pages d'erreur dédiées) et BNF-09 (menus adaptés au rôle connecté).
 */
class InterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_staff_role_sees_its_own_menu(): void
    {
        $this->actingAs(User::factory()->secretary()->create())->get('/dashboard')
            ->assertSee('Secrétariat')
            ->assertSee('Patients')
            ->assertDontSee('Utilisateurs');

        $this->actingAs(User::factory()->doctor()->create())->get('/patients')
            ->assertSee('Espace médecin')
            ->assertSee('Dossiers patients');

        $this->actingAs(User::factory()->admin()->create())->get('/personnel')
            ->assertSee('Administration')
            ->assertSee('Utilisateurs')
            ->assertDontSee(route('patients.index'));
    }

    public function test_patients_get_the_top_navigation_with_their_name(): void
    {
        $patient = User::factory()->patient()->create(['first_name' => 'Amira', 'last_name' => 'Ben Salah']);

        $this->actingAs($patient)->get('/dashboard')
            ->assertOk()
            ->assertSee('Espace patient · Amira Ben Salah')
            ->assertSee('Mes rendez-vous')
            ->assertDontSee('Secrétariat');
    }

    public function test_the_404_page_is_in_french(): void
    {
        $this->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee('Aller à l\'accueil', false);
    }

    public function test_the_403_page_offers_a_way_back(): void
    {
        $this->actingAs(User::factory()->patient()->create())
            ->get('/patients')
            ->assertForbidden()
            ->assertSee('Accès refusé')
            ->assertSee('Retour au tableau de bord');
    }

    public function test_the_500_page_shows_no_technical_detail(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Erreur du serveur', $html);
        $this->assertStringNotContainsString('Exception', $html);
    }

    public function test_the_login_page_uses_the_cabinet_branding(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Cabinet médical')
            ->assertSee('Nos médecins');
    }
}
