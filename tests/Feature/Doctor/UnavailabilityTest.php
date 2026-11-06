<?php

namespace Tests\Feature\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Unavailability;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Indisponibilités ponctuelles du médecin (BF-18, maquette 12).
 */
class UnavailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::create(2026, 11, 6, 9));
        $this->doctor = Doctor::factory()->create();
    }

    public function test_the_doctor_declares_an_unavailability(): void
    {
        $this->actingAs($this->doctor->user)
            ->post('/indisponibilites', ['starts_at' => '2026-11-16T08:00', 'ends_at' => '2026-11-16T12:00', 'reason' => 'Réunion à l\'hôpital'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/disponibilites')
            ->assertSessionMissing('warning');

        $unavailability = $this->doctor->unavailabilities()->sole();
        $this->assertSame('2026-11-16 08:00', $unavailability->starts_at->format('Y-m-d H:i'));
        $this->assertSame('Lun. 16 nov. · 08:00 – 12:00', $unavailability->periodLabel());
    }

    public function test_the_secretariat_is_warned_about_appointments_during_the_period(): void
    {
        $at = CarbonImmutable::create(2026, 11, 16);
        Appointment::factory()->for($this->doctor)->at($at->setTime(9, 0))->confirmed()->create();
        Appointment::factory()->for($this->doctor)->at($at->setTime(10, 0))->pending()->create();
        // Ne comptent pas : annulé, hors période, autre médecin.
        Appointment::factory()->for($this->doctor)->at($at->setTime(11, 0))->cancelled()->create();
        Appointment::factory()->for($this->doctor)->at($at->setTime(14, 0))->confirmed()->create();
        Appointment::factory()->at($at->setTime(9, 0))->confirmed()->create();

        $this->actingAs($this->doctor->user)
            ->post('/indisponibilites', ['starts_at' => '2026-11-16T08:00', 'ends_at' => '2026-11-16T12:00'])
            ->assertSessionHas('warning', '2 rendez-vous actifs tombent sur cette période : le secrétariat sera prévenu pour les déplacer.');
    }

    public function test_the_period_must_end_after_it_starts_and_in_the_future(): void
    {
        $this->actingAs($this->doctor->user)
            ->post('/indisponibilites', ['starts_at' => '2026-11-16T12:00', 'ends_at' => '2026-11-16T08:00'])
            ->assertSessionHasErrors('ends_at');

        $this->actingAs($this->doctor->user)
            ->post('/indisponibilites', ['starts_at' => '2026-11-01T08:00', 'ends_at' => '2026-11-02T08:00'])
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseCount('unavailabilities', 0);
    }

    public function test_the_page_lists_current_and_upcoming_unavailabilities_only(): void
    {
        Unavailability::factory()->for($this->doctor)->create([
            'starts_at' => '2026-12-21 08:00', 'ends_at' => '2026-12-26 12:00', 'reason' => 'Formation médicale',
        ]);
        Unavailability::factory()->for($this->doctor)->create([
            'starts_at' => '2026-10-01 08:00', 'ends_at' => '2026-10-02 12:00', 'reason' => 'Congé passé',
        ]);

        $this->actingAs($this->doctor->user)
            ->get('/disponibilites')
            ->assertSee('Du 21 déc. 08:00 au 26 déc. 12:00')
            ->assertSee('Formation médicale')
            ->assertDontSee('Congé passé');
    }

    public function test_the_doctor_removes_an_unavailability(): void
    {
        $unavailability = Unavailability::factory()->for($this->doctor)->create();

        $this->actingAs($this->doctor->user)
            ->delete("/indisponibilites/{$unavailability->id}")
            ->assertRedirect('/disponibilites');

        $this->assertModelMissing($unavailability);
    }

    public function test_a_doctor_cannot_remove_another_doctors_unavailability(): void
    {
        $other = Unavailability::factory()->create();

        $this->actingAs($this->doctor->user)->delete("/indisponibilites/{$other->id}")->assertForbidden();
        $this->assertModelExists($other);
    }

    public function test_only_doctors_declare_unavailabilities(): void
    {
        foreach ([User::factory()->secretary()->create(), User::factory()->admin()->create(), User::factory()->patient()->create()] as $user) {
            $this->actingAs($user)
                ->post('/indisponibilites', ['starts_at' => '2026-11-16T08:00', 'ends_at' => '2026-11-16T12:00'])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('unavailabilities', 0);
    }
}
