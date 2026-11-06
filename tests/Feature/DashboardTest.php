<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_greets_the_user_and_shows_their_role(): void
    {
        $doctor = User::factory()->doctor()->create(['first_name' => 'Salma', 'last_name' => 'Ben Ali']);

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bonjour Salma')
            ->assertSee('Médecin');
    }

    public function test_every_role_can_open_the_dashboard(): void
    {
        foreach (['doctor', 'secretary', 'admin', 'patient'] as $state) {
            $this->actingAs(User::factory()->{$state}()->create())->get('/dashboard')->assertOk();
        }
    }
}
