<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\Unavailability;
use App\Services\Slot;
use App\Services\SlotFinder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RG-11 : créneaux proposés = disponibilités − indisponibilités − créneaux réservés.
 */
class SlotFinderTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private SlotFinder $finder;

    /** Un lundi à venir, pour des tests indépendants de la date du jour. */
    private CarbonImmutable $monday;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-04 07:00'); // mercredi
        $this->monday = CarbonImmutable::parse('2026-11-09');

        $this->doctor = Doctor::factory()->create();
        Availability::factory()->for($this->doctor)->on(DayOfWeek::Monday, '08:00', '10:00')->create();
        Availability::factory()->for($this->doctor)->on(DayOfWeek::Monday, '14:00', '15:00', 20)->create();

        $this->finder = app(SlotFinder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_slots_are_cut_from_the_weekly_availabilities(): void
    {
        $slots = $this->finder->slotsOn($this->doctor, $this->monday);

        $this->assertSame(
            ['08:00', '08:30', '09:00', '09:30', '14:00', '14:20', '14:40'],
            $slots->map->label()->all()
        );
        $this->assertTrue($slots->every->isFree());
        $this->assertSame('14:20', $slots[5]->start->format('H:i'));
        $this->assertSame('14:40', $slots[5]->end->format('H:i'));
    }

    public function test_a_day_without_availability_has_no_slots(): void
    {
        $this->assertCount(0, $this->finder->slotsOn($this->doctor, $this->monday->addDay()));
        $this->assertCount(0, $this->finder->slotsOn($this->doctor, $this->monday->subDay())); // dimanche
    }

    public function test_booked_slots_are_taken_but_cancelled_ones_are_free_again(): void
    {
        Appointment::factory()->at($this->monday->setTime(8, 30))->confirmed()->create(['doctor_id' => $this->doctor->id]);
        Appointment::factory()->at($this->monday->setTime(9, 0))->pending()->create(['doctor_id' => $this->doctor->id]);
        Appointment::factory()->at($this->monday->setTime(9, 30))->cancelled()->create(['doctor_id' => $this->doctor->id]);

        $states = $this->finder->slotsOn($this->doctor, $this->monday)->mapWithKeys(fn (Slot $s) => [$s->label() => $s->state]);

        $this->assertSame(Slot::FREE, $states['08:00']);
        $this->assertSame(Slot::TAKEN, $states['08:30']);
        $this->assertSame(Slot::TAKEN, $states['09:00']);
        $this->assertSame(Slot::FREE, $states['09:30']);
    }

    public function test_an_appointment_being_moved_does_not_block_its_own_slot(): void
    {
        $appointment = Appointment::factory()->at($this->monday->setTime(8, 30))->confirmed()->create(['doctor_id' => $this->doctor->id]);

        $slot = $this->finder->slotsOn($this->doctor, $this->monday, $appointment->id)->first(fn (Slot $s) => $s->label() === '08:30');

        $this->assertTrue($slot->isFree());
    }

    public function test_unavailabilities_remove_slots(): void
    {
        Unavailability::factory()->for($this->doctor)->create([
            'starts_at' => $this->monday->setTime(9, 0),
            'ends_at' => $this->monday->setTime(15, 0),
        ]);

        $free = $this->finder->slotsOn($this->doctor, $this->monday)->filter->isFree()->map->label()->values()->all();

        $this->assertSame(['08:00', '08:30'], $free);
    }

    public function test_past_slots_are_not_bookable(): void
    {
        CarbonImmutable::setTestNow($this->monday->setTime(9, 10));

        $states = $this->finder->slotsOn($this->doctor, $this->monday)->mapWithKeys(fn (Slot $s) => [$s->label() => $s->state]);

        $this->assertSame(Slot::PAST, $states['09:00']);
        $this->assertSame(Slot::FREE, $states['09:30']);
    }

    public function test_next_free_slot(): void
    {
        Appointment::factory()->at($this->monday->setTime(8, 0))->confirmed()->create(['doctor_id' => $this->doctor->id]);

        $next = $this->finder->nextFreeSlot($this->doctor);

        $this->assertSame('2026-11-09 08:30', $next->key());
    }

    public function test_is_bookable_checks_slot_boundaries_and_booking_horizon(): void
    {
        $this->assertTrue($this->finder->isBookable($this->doctor, $this->monday->setTime(8, 30), $this->monday->setTime(9, 0)));
        // Pas aligné sur un créneau.
        $this->assertFalse($this->finder->isBookable($this->doctor, $this->monday->setTime(8, 15), $this->monday->setTime(8, 45)));
        // Hors disponibilités.
        $this->assertFalse($this->finder->isBookable($this->doctor, $this->monday->setTime(11, 0), $this->monday->setTime(11, 30)));

        // RG-14 : au plus 60 jours à l'avance (paramétrable).
        $far = $this->monday->addWeeks(7); // lundi 28 décembre : 54 jours après le 4 novembre
        $this->assertTrue($this->finder->isBookable($this->doctor, $far->setTime(8, 0), $far->setTime(8, 30)));

        Setting::create(['booking_days_ahead' => 30]);
        $this->assertFalse($this->finder->isBookable($this->doctor, $far->setTime(8, 0), $far->setTime(8, 30)));
    }
}
