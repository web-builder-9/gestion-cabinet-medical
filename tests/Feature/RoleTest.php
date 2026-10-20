<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sécurité des rôles : personne ne doit pouvoir s'attribuer
 * le rôle de médecin ou de secrétaire.
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'doctor',
        ]);

        $this->assertSame(Role::Patient, $user->fresh()->role);
    }

    public function test_role_helpers(): void
    {
        $doctor = User::factory()->doctor()->create();
        $secretary = User::factory()->secretary()->create();

        $this->assertTrue($doctor->isDoctor());
        $this->assertFalse($doctor->isSecretary());
        $this->assertTrue($secretary->hasRole(Role::Doctor, Role::Secretary));
        $this->assertFalse($secretary->hasRole(Role::Doctor));
        $this->assertEquals([$doctor->id], User::doctors()->pluck('id')->all());
    }
}
