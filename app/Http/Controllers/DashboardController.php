<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\DayPlanning;
use App\Services\Statistics;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page d'arrivée après la connexion : chaque rôle a la sienne.
 */
class DashboardController extends Controller
{
    /** Nombre de demandes en ligne affichées sur le tableau de bord. */
    public const PENDING_SHOWN = 5;

    /** Nombre de rendez-vous passés affichés au patient. */
    public const HISTORY_SHOWN = 10;

    public function __invoke(Request $request, DayPlanning $planning, Statistics $statistics): View|RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            // L'administrateur arrive sur la gestion des comptes (maquette 16).
            $user->isAdmin() => redirect()->route('admin.users.index'),
            // Le médecin arrive sur son agenda (maquette 11).
            $user->isDoctor() => redirect()->route('agenda'),
            $user->isSecretary() => $this->secretary($planning, $statistics),
            default => $this->patient($user->patient),
        };
    }

    /**
     * « Mes rendez-vous » du patient (maquette 6) : à venir, historique et
     * dernier compte rendu (BF-10, BF-11 ; RG-21 : lecture seule).
     */
    private function patient(?Patient $patient): View
    {
        if ($patient === null) {
            return view('dashboard.patient', ['patient' => null, 'upcoming' => collect(), 'history' => collect(), 'lastConsultation' => null]);
        }

        $appointments = $patient->appointments()->with('doctor.user');

        return view('dashboard.patient', [
            'patient' => $patient,
            'upcoming' => (clone $appointments)->active()->upcoming()->orderBy('starts_at')->get(),
            // Passés, et annulés même s'ils étaient prévus plus tard.
            'history' => (clone $appointments)
                ->where(fn (Builder $q) => $q->past()->orWhereNotIn('status', AppointmentStatus::activeValues()))
                ->orderByDesc('starts_at')
                ->limit(self::HISTORY_SHOWN)
                ->get(),
            'lastConsultation' => Consultation::query()
                ->with(['appointment.doctor.user', 'prescriptionItems'])
                ->whereHas('appointment', fn (Builder $q) => $q->where('patient_id', $patient->id))
                ->latest('consulted_at')
                ->first(),
        ]);
    }

    /**
     * Tableau de bord du secrétariat (maquette 7).
     */
    private function secretary(DayPlanning $planning, Statistics $statistics): View
    {
        $today = CarbonImmutable::today();
        $doctors = Doctor::activeByName();

        // Rendez-vous du jour (hors annulés) : terminés et encore à venir.
        $todayStatuses = Appointment::onDate($today)->toBase()->pluck('status')->countBy();
        $pending = Appointment::query()->with(['patient.user', 'doctor.user'])
            ->where('status', AppointmentStatus::Pending)->upcoming();

        return view('dashboard.secretary', [
            'today' => $today,
            'doctors' => $doctors,
            'todayCount' => $todayStatuses->except(AppointmentStatus::Cancelled->value)->sum(),
            'todayCompleted' => $todayStatuses->get(AppointmentStatus::Completed->value, 0),
            'todayUpcoming' => Appointment::onDate($today)->active()->upcoming()->count(),
            'pendingCount' => (clone $pending)->count(),
            'pendingAppointments' => $pending->latest()->limit(self::PENDING_SHOWN)->get(),
            'month' => $statistics->forMonth($today),
            'newPatients' => Patient::where('created_at', '>=', $today->startOfMonth())->count(),
            // Rendez-vous pris avant qu'une indisponibilité soit déclarée : à déplacer.
            'toMove' => Appointment::with(['patient.user', 'doctor.user'])
                ->active()->upcoming()->duringUnavailability()->orderBy('starts_at')->get(),
            'planning' => $planning->for($doctors, $today),
        ]);
    }
}
