<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spécialités (BF-25, maquette 17) : RG-06 et RG-07.
 */
class SpecialtyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_the_settings_page_lists_specialties_with_their_doctor_count(): void
    {
        $cardiology = Specialty::factory()->create(['name' => 'Cardiologie']);
        Doctor::factory()->count(2)->for($cardiology)->create();
        Specialty::factory()->create(['name' => 'Dermatologie']);

        $this->actingAs($this->admin)
            ->get('/admin/parametres')
            ->assertOk()
            ->assertSeeInOrder(['Cardiologie', '2', 'Dermatologie', '0'])
            ->assertSee('Une spécialité attribuée à un médecin ne peut pas être supprimée.');
    }

    public function test_the_admin_adds_a_specialty(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/specialites', ['name' => 'Ophtalmologie'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/parametres');

        $this->assertDatabaseHas('specialties', ['name' => 'Ophtalmologie']);
    }

    public function test_a_specialty_name_is_unique(): void
    {
        Specialty::factory()->create(['name' => 'Pédiatrie']);

        $this->actingAs($this->admin)
            ->post('/admin/specialites', ['name' => 'Pédiatrie', 'specialty_form' => 'new'])
            ->assertSessionHasErrorsIn('specialty', 'name');

        $this->assertDatabaseCount('specialties', 1);
    }

    public function test_the_admin_renames_a_specialty(): void
    {
        $specialty = Specialty::factory()->create(['name' => 'Medecine generale']);

        $this->actingAs($this->admin)
            ->put("/admin/specialites/{$specialty->id}", ['name' => 'Médecine générale', 'description' => 'Adultes et enfants.'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/parametres');

        $this->assertSame('Médecine générale', $specialty->fresh()->name);
        $this->assertSame('Adultes et enfants.', $specialty->fresh()->description);
    }

    public function test_an_unused_specialty_can_be_deleted(): void
    {
        $specialty = Specialty::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/specialites/{$specialty->id}")
            ->assertSessionHas('status');

        $this->assertModelMissing($specialty);
    }

    public function test_a_specialty_given_to_a_doctor_cannot_be_deleted(): void
    {
        $specialty = Specialty::factory()->create(['name' => 'Pédiatrie']);
        Doctor::factory()->for($specialty)->create();

        $this->actingAs($this->admin)
            ->delete("/admin/specialites/{$specialty->id}")
            ->assertRedirect('/admin/parametres')
            ->assertSessionHas('error', 'La spécialité « Pédiatrie » est attribuée à au moins un médecin : elle ne peut pas être supprimée.');

        $this->assertModelExists($specialty);
    }

    public function test_the_delete_button_is_only_shown_for_unused_specialties(): void
    {
        $used = Specialty::factory()->create(['name' => 'Pédiatrie']);
        Doctor::factory()->for($used)->create();
        $unused = Specialty::factory()->create(['name' => 'Dermatologie']);

        $this->actingAs($this->admin)
            ->get('/admin/parametres')
            ->assertSee('aria-label="Supprimer la spécialité Dermatologie"', false)
            ->assertDontSee('aria-label="Supprimer la spécialité Pédiatrie"', false);
    }

    public function test_only_the_admin_manages_specialties(): void
    {
        $specialty = Specialty::factory()->create();

        foreach ([User::factory()->doctor()->create(), User::factory()->secretary()->create(), User::factory()->patient()->create()] as $user) {
            $this->actingAs($user)->post('/admin/specialites', ['name' => 'Neurologie'])->assertForbidden();
            $this->actingAs($user)->put("/admin/specialites/{$specialty->id}", ['name' => 'Autre'])->assertForbidden();
            $this->actingAs($user)->delete("/admin/specialites/{$specialty->id}")->assertForbidden();
        }

        $this->assertModelExists($specialty);
        $this->assertDatabaseMissing('specialties', ['name' => 'Neurologie']);
    }
}
