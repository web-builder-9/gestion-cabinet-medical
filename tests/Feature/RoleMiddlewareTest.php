<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use ValueError;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Routes créées uniquement pour ce test.
        Route::middleware(['web', 'auth', 'role:doctor'])
            ->get('/_test/medecin', fn () => 'zone médecin');

        Route::middleware(['web', 'auth', 'role:doctor,secretary'])
            ->get('/_test/personnel', fn () => 'zone personnel');

        Route::middleware(['web', 'auth', 'role:docteur'])
            ->get('/_test/faute-de-frappe', fn () => 'jamais affiché');
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/_test/medecin')->assertRedirect('/login');
    }

    public function test_the_right_role_gets_through(): void
    {
        $this->actingAs(User::factory()->doctor()->create())
            ->get('/_test/medecin')
            ->assertOk()
            ->assertSee('zone médecin');
    }

    public function test_the_wrong_role_gets_a_403_in_french(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->get('/_test/medecin')
            ->assertForbidden()
            ->assertSee('Accès refusé');
    }

    public function test_several_roles_can_be_allowed(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->get('/_test/personnel')
            ->assertOk();

        $this->actingAs(User::factory()->patient()->create())
            ->get('/_test/personnel')
            ->assertForbidden();
    }

    public function test_an_unknown_role_in_a_route_fails_loudly(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(ValueError::class);

        $this->actingAs(User::factory()->doctor()->create())->get('/_test/faute-de-frappe');
    }
}
