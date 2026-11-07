<?php

namespace Tests\Feature\Patients;

use App\Http\Controllers\PatientController;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PatientIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_staff_can_see_the_patient_list(): void
    {
        Patient::factory()->create(['first_name' => 'Amira', 'last_name' => 'Trabelsi']);

        foreach ([User::factory()->doctor()->create(), User::factory()->secretary()->create()] as $user) {
            $this->actingAs($user)
                ->get('/patients')
                ->assertOk()
                ->assertSee('Trabelsi Amira');
        }
    }

    public function test_patients_and_guests_cannot_see_the_list(): void
    {
        $this->get('/patients')->assertRedirect('/login');

        $this->actingAs(User::factory()->patient()->create())
            ->get('/patients')
            ->assertForbidden();
    }

    public function test_the_menu_shows_the_patients_link_to_the_staff_only(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->get('/dashboard')
            ->assertSee(route('patients.index'));

        $this->actingAs(User::factory()->patient()->create())
            ->get('/dashboard')
            ->assertDontSee(route('patients.index'));
    }

    public function test_the_list_can_be_searched(): void
    {
        Patient::factory()->create(['first_name' => 'Amira', 'last_name' => 'Trabelsi']);
        Patient::factory()->create(['first_name' => 'Karim', 'last_name' => 'Gharbi']);

        $this->actingAs(User::factory()->secretary()->create())
            ->get('/patients?q=gharb')
            ->assertOk()
            ->assertSee('Gharbi Karim')
            ->assertDontSee('Trabelsi Amira');
    }

    public function test_an_empty_search_says_so(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->get('/patients?q=inconnu')
            ->assertSee('Aucun patient ne correspond');
    }

    public function test_the_list_is_paginated_and_keeps_the_search(): void
    {
        Patient::factory()->count(PatientController::PER_PAGE + 5)->create(['last_name' => 'Jebali']);

        $response = $this->actingAs(User::factory()->doctor()->create())->get('/patients?q=Jebali');

        $response->assertViewHas('patients', function (LengthAwarePaginator $patients) {
            return $patients->count() === PatientController::PER_PAGE && $patients->total() === PatientController::PER_PAGE + 5;
        });
        $response->assertSee('q=Jebali&amp;page=2', false);
    }

    public function test_the_list_is_sorted_by_name(): void
    {
        Patient::factory()->create(['first_name' => 'Zied', 'last_name' => 'Zouari']);
        Patient::factory()->create(['first_name' => 'Ali', 'last_name' => 'Ayari']);

        $this->actingAs(User::factory()->doctor()->create())
            ->get('/patients')
            ->assertSeeInOrder(['Ayari Ali', 'Zouari Zied']);
    }
}
