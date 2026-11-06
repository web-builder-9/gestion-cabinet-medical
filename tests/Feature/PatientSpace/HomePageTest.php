<?php

namespace Tests\Feature\PatientSpace;

use App\Enums\DayOfWeek;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accueil du visiteur (maquette 1) : BF-01, BF-02 et prochain créneau (BF-07).
 */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $benAli;

    private Doctor $saidi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::create(2026, 11, 2, 7)); // lundi
        Setting::create(['address' => 'Avenue Habib Bourguiba, Midoun, Djerba', 'phone' => '75 600 600']);

        $this->benAli = Doctor::factory()->withUser(['first_name' => 'Salma', 'last_name' => 'Ben Ali'])
            ->for(Specialty::factory()->state(['name' => 'Médecine générale']))
            ->create(['presentation' => 'Suivi des adultes et des enfants.']);
        Availability::factory()->for($this->benAli)->on(DayOfWeek::Thursday, '10:30', '12:00')->create();

        $this->saidi = Doctor::factory()->withUser(['first_name' => 'Mehdi', 'last_name' => 'Saidi'])
            ->for(Specialty::factory()->state(['name' => 'Pédiatrie']))
            ->create();
    }

    public function test_visitors_see_the_doctors_and_their_next_slot(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Prenez rendez-vous en ligne, 24 h/24.')
            ->assertSee('Dr Salma Ben Ali')
            ->assertSee('Médecine générale')
            ->assertSee('Suivi des adultes et des enfants.')
            ->assertSee('Prochain créneau : <strong class="font-semibold">jeudi 5 nov. à 10:30</strong>', false)
            ->assertSee('Dr Mehdi Saidi')
            ->assertSee('Aucun créneau libre pour le moment.')
            ->assertSee('Avenue Habib Bourguiba, Midoun, Djerba')
            ->assertSee('Créer un compte');
    }

    public function test_doctors_can_be_searched_by_name_or_specialty(): void
    {
        $this->get('/?q=pédiatrie')->assertSee('Dr Mehdi Saidi')->assertDontSee('Dr Salma Ben Ali');
        $this->get('/?q=Ben+Ali')->assertSee('Dr Salma Ben Ali')->assertDontSee('Dr Mehdi Saidi');
        $this->get('/?specialite='.$this->saidi->specialty_id)->assertSee('Dr Mehdi Saidi')->assertDontSee('Dr Salma Ben Ali');
        $this->get('/?q=dermatologue')->assertSee('Aucun médecin ne correspond à votre recherche.');
    }

    public function test_inactive_doctors_are_not_listed(): void
    {
        $this->saidi->user->forceFill(['is_active' => false])->save();

        $this->get('/')->assertDontSee('Dr Mehdi Saidi');
    }

    public function test_booking_from_the_home_page_asks_to_sign_in(): void
    {
        $this->get(route('booking.create', ['medecin' => $this->benAli->id]))->assertRedirect('/login');
    }

    public function test_guests_are_sent_to_the_login_page_for_private_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_signed_in_users_reach_their_space_from_the_home_page(): void
    {
        $this->actingAs(User::factory()->secretary()->create())->get('/')->assertSee('Mon espace');
    }
}
