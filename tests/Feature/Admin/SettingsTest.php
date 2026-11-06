<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Statistics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paramètres du cabinet (BF-26) et activité du mois (BF-27), maquette 17.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'cabinet_name' => 'Cabinet médical de Midoun',
            'address' => 'Avenue Habib Bourguiba, Midoun',
            'phone' => '75600600',
            'opening_time' => '08:30',
            'closing_time' => '18:00',
            'saturday_opening_time' => '08:00',
            'saturday_closing_time' => '12:00',
            'default_slot_minutes' => '20',
            'cancellation_hours' => '48',
            'booking_days_ahead' => '30',
            'reminder_hours' => '24',
        ], $overrides);
    }

    public function test_the_page_shows_the_default_settings(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/parametres')
            ->assertOk()
            ->assertSee('Paramètres du cabinet')
            ->assertSee('value="08:00"', false)
            ->assertSee('value="17:00"', false)
            ->assertSee("Jusqu'à 24 h avant", false)
            ->assertSee("60 jours à l'avance", false);
    }

    public function test_the_admin_saves_the_settings(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/parametres', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/parametres');

        $settings = Setting::current();
        $this->assertSame('Cabinet médical de Midoun', $settings->cabinet_name);
        $this->assertSame('75 600 600', $settings->phone);
        $this->assertSame('08:30', $settings->time('opening_time'));
        $this->assertSame(20, $settings->default_slot_minutes);
        $this->assertSame(48, $settings->cancellation_hours);
        $this->assertSame(30, $settings->booking_days_ahead);
        $this->assertTrue($settings->isOpenOnSaturday());

        // Une seule ligne de paramètres, même après plusieurs enregistrements.
        $this->actingAs($this->admin)->put('/admin/parametres', $this->validData(['saturday_opening_time' => '', 'saturday_closing_time' => '']));
        $this->assertDatabaseCount('settings', 1);
        $this->assertFalse(Setting::current()->isOpenOnSaturday());
    }

    public function test_closing_must_be_after_opening(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/parametres', $this->validData(['opening_time' => '18:00', 'closing_time' => '08:00']))
            ->assertSessionHasErrors(['closing_time' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture.']);
    }

    public function test_saturday_needs_both_hours(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/parametres', $this->validData(['saturday_closing_time' => '']))
            ->assertSessionHasErrors('saturday_closing_time');
    }

    public function test_only_the_proposed_values_are_accepted(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/parametres', $this->validData(['default_slot_minutes' => '7', 'booking_days_ahead' => '1000']))
            ->assertSessionHasErrors(['default_slot_minutes', 'booking_days_ahead']);
    }

    public function test_only_the_admin_changes_the_settings(): void
    {
        foreach ([User::factory()->doctor()->create(), User::factory()->secretary()->create(), User::factory()->patient()->create()] as $user) {
            $this->actingAs($user)->get('/admin/parametres')->assertForbidden();
            $this->actingAs($user)->put('/admin/parametres', $this->validData())->assertForbidden();
        }

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_the_monthly_statistics_count_appointments_no_shows_and_cancellations(): void
    {
        $month = CarbonImmutable::create(2026, 10, 1);
        $at = fn (int $day) => $month->setDay($day)->setTime(9, 0);

        Appointment::factory()->count(7)->at($at(5))->completed()->create();
        Appointment::factory()->count(3)->at($at(6))->noShow()->create();
        Appointment::factory()->count(2)->at($at(7))->cancelled()->create();
        Appointment::factory()->at($at(8))->confirmed()->create();
        // Autre mois : non compté.
        Appointment::factory()->at($month->addMonth()->setTime(9, 0))->completed()->create();

        $stats = (new Statistics)->forMonth($month);

        $this->assertSame(13, $stats['appointments']);
        $this->assertSame(7, $stats['completed']);
        $this->assertSame(3, $stats['no_shows']);
        $this->assertSame(2, $stats['cancellations']);
        // Absences parmi les rendez-vous qui ont eu lieu : 3 / (7 + 3).
        $this->assertSame(30.0, $stats['no_show_rate']);
    }

    public function test_the_rate_is_zero_when_nothing_happened_yet(): void
    {
        $this->assertSame(0.0, (new Statistics)->forMonth(CarbonImmutable::create(2026, 1, 1))['no_show_rate']);
    }

    public function test_the_page_shows_the_activity_of_the_current_month(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 20, 10));

        Appointment::factory()->count(9)->at(now()->subDays(5))->completed()->create();
        Appointment::factory()->at(now()->subDays(4))->noShow()->create();

        $this->actingAs($this->admin)
            ->get('/admin/parametres')
            ->assertSee("Activité d'octobre")
            ->assertSee('10,0 %')
            ->assertSee("d'absences (1)", false);
    }
}
