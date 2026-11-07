<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Services\AppointmentNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Changements d'état d'un rendez-vous par le personnel (BF-14, BF-16).
 * Chaque changement est historisé automatiquement (RG-23, voir Appointment::booted)
 * et le patient est prévenu par e-mail (BF-28).
 */
class AppointmentStatusController extends Controller
{
    public function confirm(Appointment $appointment, AppointmentNotifier $notifier): RedirectResponse
    {
        Gate::authorize('confirm', $appointment);

        $appointment->changeStatus(AppointmentStatus::Confirmed);
        $notifier->send($appointment, NotificationType::Confirmation); // BF-28

        return back()->with('status', "Le rendez-vous de {$appointment->patient->full_name} ({$this->when($appointment)}) est confirmé.");
    }

    /**
     * Annuler libère le créneau ; le rendez-vous reste dans l'historique (RG-22).
     */
    public function cancel(Appointment $appointment, AppointmentNotifier $notifier): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        $appointment->changeStatus(AppointmentStatus::Cancelled);
        $notifier->send($appointment, NotificationType::Cancellation); // BF-28

        return back()->with('status', "Le rendez-vous de {$appointment->patient->full_name} ({$this->when($appointment)}) est annulé : le créneau est libéré.");
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('markNoShow', $appointment);

        $appointment->changeStatus(AppointmentStatus::NoShow);

        return back()->with('status', "{$appointment->patient->full_name} est noté(e) absent(e) au rendez-vous du {$this->when($appointment)}.");
    }

    /**
     * « jeu. 5 nov. à 10:30 ».
     */
    private function when(Appointment $appointment): string
    {
        return $appointment->starts_at->translatedFormat('D j M').' à '.$appointment->starts_at->format('H:i');
    }
}
