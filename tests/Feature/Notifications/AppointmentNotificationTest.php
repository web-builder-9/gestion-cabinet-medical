<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\Setting;
use App\Notifications\AppointmentNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * E-mails au patient (BF-28) et rappel avant le rendez-vous (BF-29).
 */
class AppointmentNotificationTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->createSchedule();
        Setting::create(['phone' => '75 600 600', 'address' => 'Avenue Habib Bourguiba, Midoun, Djerba']);
    }

    private function assertNoticeSent(NotificationType $type, string $subject): void
    {
        Notification::assertSentTo($this->patient->user, AppointmentNotice::class, function (AppointmentNotice $notice) use ($type, $subject) {
            return $notice->type === $type && $notice->toMail($this->patient->user)->subject === $subject;
        });
    }

    public function test_a_booking_by_the_secretariat_is_confirmed_by_email_and_logged(): void
    {
        $this->actingAs($this->secretary)->post('/rendez-vous', [
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00',
        ]);

        $this->assertNoticeSent(NotificationType::Confirmation, 'Rendez-vous confirmé');

        $log = Appointment::sole()->notifications()->sole();
        $this->assertSame(NotificationType::Confirmation, $log->type);
        $this->assertTrue($log->is_sent);
        $this->assertNotNull($log->sent_at);
    }

    public function test_an_online_request_is_acknowledged_then_confirmed(): void
    {
        $this->actingAs($this->patient->user)->post('/prendre-rendez-vous', [
            'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00',
        ]);
        $this->assertNoticeSent(NotificationType::Confirmation, 'Demande de rendez-vous reçue');

        $this->actingAs($this->secretary)->patch('/rendez-vous/'.Appointment::sole()->id.'/confirmer');
        $this->assertNoticeSent(NotificationType::Confirmation, 'Rendez-vous confirmé');
        $this->assertSame(2, Appointment::sole()->notifications()->count());
    }

    public function test_moving_and_cancelling_are_notified(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();

        // Changer seulement le motif ne prévient pas le patient.
        $this->actingAs($this->secretary)->put("/rendez-vous/{$appointment->id}", [
            'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00', 'reason' => 'Bilan',
        ]);
        Notification::assertNothingSent();

        $this->actingAs($this->secretary)->put("/rendez-vous/{$appointment->id}", [
            'doctor_id' => $this->doctor->id, 'date' => '2026-11-06', 'time' => '09:00',
        ]);
        $this->assertNoticeSent(NotificationType::Modification, 'Rendez-vous modifié');

        $this->actingAs($this->patient->user)->patch("/mes-rendez-vous/{$appointment->id}/annuler");
        $this->assertNoticeSent(NotificationType::Cancellation, 'Rendez-vous annulé');
    }

    public function test_a_patient_without_email_is_not_notified_but_it_is_logged(): void
    {
        $this->patient->user->update(['email' => null]);

        $this->actingAs($this->secretary)->post('/rendez-vous', [
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00',
        ]);

        Notification::assertNothingSent();
        $this->assertFalse(Appointment::sole()->notifications()->sole()->is_sent);

        $this->actingAs($this->secretary)
            ->get('/rendez-vous/'.Appointment::sole()->id)
            ->assertSee('non envoyé (pas d&#039;adresse e-mail)', false);
    }

    public function test_the_email_is_written_in_french(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();

        $mail = (new AppointmentNotice($appointment, NotificationType::Reminder))->toMail($this->patient->user);

        $this->assertSame('Rappel : rendez-vous le 5 novembre à 10:00', $mail->subject);
        $this->assertSame('Bonjour Hedi,', $mail->greeting);
        $this->assertContains('**Jeudi 5 novembre 2026 à 10:00** avec Dr Salma Ben Ali.', $mail->introLines);
        $this->assertContains('En cas d\'empêchement, prévenez le cabinet au 75 600 600.', $mail->introLines);
        $this->assertSame('Voir mes rendez-vous', $mail->actionText);
    }

    public function test_reminders_are_sent_once_for_confirmed_appointments_of_the_next_24_hours(): void
    {
        $this->travelTo($this->thursday('09:00')->subDay()); // mercredi 09:00
        $soon = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('08:30'))->confirmed()->create();
        // Pas de rappel : en attente, trop loin, annulé.
        Appointment::factory()->for($this->doctor)->at($this->thursday('08:00'))->pending()->create();
        Appointment::factory()->for($this->doctor)->at($this->thursday('11:00'))->confirmed()->create();
        Appointment::factory()->for($this->doctor)->at($this->thursday('08:00'))->cancelled()->create();

        $this->artisan('rendez-vous:rappels')
            ->expectsOutput('1 rappel(s) traité(s) pour les rendez-vous des 24 prochaines heures.')
            ->assertSuccessful();
        $this->artisan('rendez-vous:rappels')->expectsOutput('0 rappel(s) traité(s) pour les rendez-vous des 24 prochaines heures.');

        Notification::assertSentToTimes($this->patient->user, AppointmentNotice::class, 1);
        $this->assertSame(NotificationType::Reminder, $soon->notifications()->sole()->type);
    }

    public function test_the_reminder_delay_comes_from_the_settings(): void
    {
        Setting::current()->update(['reminder_hours' => 48]);
        $this->travelTo($this->thursday('09:00')->subDays(2));
        Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('08:30'))->confirmed()->create();

        $this->artisan('rendez-vous:rappels')->expectsOutput('1 rappel(s) traité(s) pour les rendez-vous des 48 prochaines heures.');
    }

    public function test_the_reminder_is_scheduled_every_hour(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('rendez-vous:rappels')->assertSuccessful();
    }
}
