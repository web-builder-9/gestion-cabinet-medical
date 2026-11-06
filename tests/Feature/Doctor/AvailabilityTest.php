<?php

namespace Tests\Feature\Doctor;

use App\Enums\DayOfWeek;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Semaine type du médecin (BF-17, maquette 12) : RG-09 et RG-10.
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Cabinet : 08:00-17:00 en semaine, 08:00-12:00 le samedi.
        Setting::create(['saturday_opening_time' => '08:00', 'saturday_closing_time' => '12:00']);
        $this->doctor = Doctor::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'day_of_week' => DayOfWeek::Monday->value,
            'start_time' => '14:00',
            'end_time' => '17:00',
            'slot_minutes' => 30,
        ], $overrides);
    }

    private function addMondayMorning(): Availability
    {
        return Availability::factory()->for($this->doctor)->on(DayOfWeek::Monday, '08:00', '12:00')->create();
    }

    public function test_the_doctor_sees_their_week_and_the_cabinet_hours(): void
    {
        $this->addMondayMorning();
        Availability::factory()->on(DayOfWeek::Tuesday, '09:00', '11:00')->create(); // autre médecin

        $this->actingAs($this->doctor->user)
            ->get('/disponibilites')
            ->assertOk()
            ->assertSee('Semaine type')
            ->assertSee('Horaires du cabinet : 08:00 – 17:00')
            ->assertSeeInOrder(['Lundi', '08:00', '12:00', '30 min'])
            ->assertDontSee('09:00');
    }

    public function test_the_doctor_adds_a_range(): void
    {
        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/disponibilites');

        $availability = $this->doctor->availabilities()->sole();
        $this->assertSame(DayOfWeek::Monday, $availability->day_of_week);
        $this->assertSame('14:00', $availability->startLabel());
        $this->assertSame(30, $availability->slot_minutes);
    }

    public function test_two_ranges_of_the_same_doctor_cannot_overlap(): void
    {
        $this->addMondayMorning();

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['start_time' => '11:00', 'end_time' => '13:00']))
            ->assertSessionHasErrors([
                'start_time' => "Cette plage chevauche « Lundi 08:00 – 12:00 ». Deux plages d'un même médecin ne peuvent pas se chevaucher.",
            ]);

        $this->assertSame(1, $this->doctor->availabilities()->count());
    }

    public function test_touching_ranges_and_other_days_or_doctors_are_accepted(): void
    {
        $this->addMondayMorning();
        Availability::factory()->on(DayOfWeek::Monday, '12:00', '14:00')->create(); // autre médecin

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['start_time' => '12:00', 'end_time' => '14:00']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['day_of_week' => DayOfWeek::Tuesday->value, 'start_time' => '08:00', 'end_time' => '12:00']))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $this->doctor->availabilities()->count());
    }

    public function test_a_range_stays_within_the_cabinet_hours(): void
    {
        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['start_time' => '07:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors(['start_time' => 'La plage doit rester dans les horaires du cabinet (Lundi : 08:00 – 17:00).']);

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['day_of_week' => DayOfWeek::Saturday->value, 'start_time' => '10:00', 'end_time' => '13:00']))
            ->assertSessionHasErrors(['start_time' => 'La plage doit rester dans les horaires du cabinet (Samedi : 08:00 – 12:00).']);
    }

    public function test_no_range_on_saturday_when_the_cabinet_is_closed(): void
    {
        Setting::current()->update(['saturday_opening_time' => null, 'saturday_closing_time' => null]);

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['day_of_week' => DayOfWeek::Saturday->value, 'start_time' => '08:00', 'end_time' => '10:00']))
            ->assertSessionHasErrors(['start_time' => 'Le cabinet est fermé le samedi.']);
    }

    public function test_invalid_ranges_are_rejected(): void
    {
        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['start_time' => '15:00', 'end_time' => '14:00']))
            ->assertSessionHasErrors(['end_time' => 'L\'heure de fin doit être après l\'heure de début.']);

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['start_time' => '14:00', 'end_time' => '14:20', 'slot_minutes' => 30]))
            ->assertSessionHasErrors(['start_time' => 'La plage doit contenir au moins un créneau de 30 min.']);

        $this->actingAs($this->doctor->user)
            ->post('/disponibilites', $this->validData(['day_of_week' => 7, 'slot_minutes' => 25]))
            ->assertSessionHasErrors(['day_of_week', 'slot_minutes']);
    }

    public function test_the_doctor_edits_a_range_without_overlapping_itself(): void
    {
        $availability = $this->addMondayMorning();

        $this->actingAs($this->doctor->user)
            ->get("/disponibilites/{$availability->id}/modifier")
            ->assertOk()
            ->assertSee('value="08:00"', false);

        $this->actingAs($this->doctor->user)
            ->put("/disponibilites/{$availability->id}", $this->validData(['start_time' => '09:00', 'end_time' => '12:00', 'slot_minutes' => 20]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/disponibilites');

        $availability->refresh();
        $this->assertSame('09:00', $availability->startLabel());
        $this->assertSame(20, $availability->slot_minutes);
    }

    public function test_the_doctor_deletes_a_range(): void
    {
        $availability = $this->addMondayMorning();

        $this->actingAs($this->doctor->user)
            ->delete("/disponibilites/{$availability->id}")
            ->assertRedirect('/disponibilites');

        $this->assertModelMissing($availability);
    }

    public function test_a_doctor_cannot_touch_another_doctors_ranges(): void
    {
        $other = Availability::factory()->on(DayOfWeek::Monday, '08:00', '12:00')->create();

        $this->actingAs($this->doctor->user)->get("/disponibilites/{$other->id}/modifier")->assertForbidden();
        $this->actingAs($this->doctor->user)->put("/disponibilites/{$other->id}", $this->validData())->assertForbidden();
        $this->actingAs($this->doctor->user)->delete("/disponibilites/{$other->id}")->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_only_doctors_open_the_availability_page(): void
    {
        foreach ([User::factory()->secretary()->create(), User::factory()->admin()->create(), User::factory()->patient()->create()] as $user) {
            $this->actingAs($user)->get('/disponibilites')->assertForbidden();
            $this->actingAs($user)->post('/disponibilites', $this->validData())->assertForbidden();
        }

        $this->assertDatabaseCount('availabilities', 0);
    }

    public function test_the_menu_shows_the_availability_link_to_doctors(): void
    {
        $this->actingAs($this->doctor->user)->get('/patients')->assertSee('Mes disponibilités');
        $this->actingAs(User::factory()->secretary()->create())->get('/patients')->assertDontSee('Mes disponibilités');
    }
}
