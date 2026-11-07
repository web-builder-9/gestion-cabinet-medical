<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RG-17 (transitions d'états) et RG-23 (historique de chaque changement).
 */
class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{AppointmentStatus, AppointmentStatus, bool}>
     */
    public static function transitions(): array
    {
        $s = fn (string $name) => constant(AppointmentStatus::class.'::'.$name);

        return [
            'En attente → Confirmé' => [$s('Pending'), $s('Confirmed'), true],
            'En attente → Annulé' => [$s('Pending'), $s('Cancelled'), true],
            'En attente → Terminé' => [$s('Pending'), $s('Completed'), false],
            'En attente → Absent' => [$s('Pending'), $s('NoShow'), false],
            'Confirmé → En attente' => [$s('Confirmed'), $s('Pending'), true],
            'Confirmé → Annulé' => [$s('Confirmed'), $s('Cancelled'), true],
            'Confirmé → Terminé' => [$s('Confirmed'), $s('Completed'), true],
            'Confirmé → Absent' => [$s('Confirmed'), $s('NoShow'), true],
            'Annulé → Confirmé' => [$s('Cancelled'), $s('Confirmed'), false],
            'Terminé → Annulé' => [$s('Completed'), $s('Cancelled'), false],
            'Absent → Confirmé' => [$s('NoShow'), $s('Confirmed'), false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_allowed_transitions(AppointmentStatus $from, AppointmentStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_final_states_and_slot_occupation(): void
    {
        $this->assertTrue(AppointmentStatus::Pending->blocksSlot());
        $this->assertTrue(AppointmentStatus::Confirmed->blocksSlot());

        foreach ([AppointmentStatus::Cancelled, AppointmentStatus::Completed, AppointmentStatus::NoShow] as $final) {
            $this->assertTrue($final->isFinal());
            $this->assertSame([], $final->allowedTransitions());
        }
    }

    public function test_a_forbidden_transition_throws_and_changes_nothing(): void
    {
        $appointment = Appointment::factory()->completed()->create();

        try {
            $appointment->changeStatus(AppointmentStatus::Confirmed);
            $this->fail('La transition aurait dû être refusée.');
        } catch (InvalidStatusTransition $e) {
            $this->assertSame('Un rendez-vous « Terminé » ne peut pas passer à « Confirmé ».', $e->getMessage());
        }

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_every_status_change_is_recorded_with_its_author(): void
    {
        $patient = User::factory()->patient()->create();
        $secretary = User::factory()->secretary()->create();

        $this->actingAs($patient);
        $appointment = Appointment::factory()->pending()->create();

        $this->actingAs($secretary);
        $appointment->changeStatus(AppointmentStatus::Confirmed);
        $appointment->changeStatus(AppointmentStatus::Cancelled);

        $history = $appointment->statusChanges()->get();

        $this->assertCount(3, $history);
        $this->assertNull($history[0]->old_status);
        $this->assertSame(AppointmentStatus::Pending, $history[0]->new_status);
        $this->assertTrue($history[0]->author->is($patient));

        $this->assertSame(AppointmentStatus::Pending, $history[1]->old_status);
        $this->assertSame(AppointmentStatus::Confirmed, $history[1]->new_status);
        $this->assertSame(AppointmentStatus::Confirmed, $history[2]->old_status);
        $this->assertSame(AppointmentStatus::Cancelled, $history[2]->new_status);
        $this->assertTrue($history[2]->author->is($secretary));
    }

    public function test_changes_without_a_logged_in_user_are_attributed_to_the_system(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $this->assertNull($appointment->statusChanges()->first()->user_id);
    }

    public function test_editing_other_fields_does_not_add_history(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();
        $appointment->update(['reason' => 'Nouveau motif']);

        $this->assertCount(1, $appointment->statusChanges()->get());
    }
}
