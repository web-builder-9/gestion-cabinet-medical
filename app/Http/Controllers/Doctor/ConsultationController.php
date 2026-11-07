<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\ConsultationRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Consultations du médecin (BF-20, BF-21, maquette 14) : compte rendu,
 * traitement prescrit et passage du rendez-vous à « Terminé » (RG-18).
 */
class ConsultationController extends Controller
{
    /**
     * Consultations du jour et derniers comptes rendus du médecin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Consultation::class);

        $doctor = $request->user()->doctor;

        return view('consultations.index', [
            'today' => $doctor->appointments()->with(['patient.user', 'consultation'])
                ->onDate(CarbonImmutable::today())
                ->whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Completed, AppointmentStatus::NoShow])
                ->orderBy('starts_at')
                ->get(),
            'recent' => Consultation::with(['appointment.patient.user'])
                ->whereHas('appointment', fn (Builder $q) => $q->where('doctor_id', $doctor->id))
                ->latest('consulted_at')
                ->limit(15)
                ->get(),
        ]);
    }

    public function create(Appointment $appointment): View
    {
        Gate::authorize('create', [Consultation::class, $appointment]);

        return view('consultations.form', $this->formData($appointment, new Consultation(['reason' => $appointment->reason])));
    }

    /**
     * Compte rendu, ordonnance et changement d'état : tout ou rien (transaction).
     */
    public function store(ConsultationRequest $request, Appointment $appointment): RedirectResponse
    {
        $consultation = DB::transaction(function () use ($request, $appointment) {
            $consultation = $appointment->consultation()->create([...$request->report(), 'consulted_at' => now()]);
            $consultation->prescriptionItems()->createMany($request->prescriptionItems());

            // RG-18 : le rendez-vous passe à « Terminé » (historisé, RG-23).
            $appointment->changeStatus(AppointmentStatus::Completed);

            return $consultation;
        });

        return $this->redirectAfterSaving($request, $consultation, 'Consultation enregistrée : le rendez-vous est terminé.');
    }

    public function show(Consultation $consultation): View
    {
        Gate::authorize('view', $consultation);

        $consultation->load(['appointment.patient.user', 'appointment.doctor.user', 'prescriptionItems']);

        return view('consultations.show', ['consultation' => $consultation]);
    }

    public function edit(Consultation $consultation): View
    {
        Gate::authorize('update', $consultation);

        return view('consultations.form', $this->formData($consultation->appointment, $consultation));
    }

    /**
     * RG-20 : seul le médecin auteur modifie le compte rendu ; l'ordonnance est remplacée.
     */
    public function update(ConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        DB::transaction(function () use ($request, $consultation) {
            $consultation->update($request->report());
            $consultation->prescriptionItems()->delete();
            $consultation->prescriptionItems()->createMany($request->prescriptionItems());
        });

        return $this->redirectAfterSaving($request, $consultation, 'Compte rendu modifié.');
    }

    /**
     * Données du formulaire : patient, dossier médical (allergies), historique.
     *
     * @return array<string, mixed>
     */
    private function formData(Appointment $appointment, Consultation $consultation): array
    {
        $appointment->loadMissing(['patient.user', 'patient.medicalRecord']);

        return [
            'appointment' => $appointment,
            'consultation' => $consultation,
            'patient' => $appointment->patient,
            'record' => $appointment->patient->medicalRecord,
            'items' => $consultation->exists
                ? $consultation->prescriptionItems->map->only(['medication', 'dosage', 'posology', 'duration'])->all()
                : [],
            // Consultations précédentes du patient, tous médecins (BF-20).
            'history' => Consultation::with(['appointment.doctor.user', 'prescriptionItems'])
                ->whereHas('appointment', fn (Builder $q) => $q->where('patient_id', $appointment->patient_id))
                ->when($consultation->exists, fn (Builder $q) => $q->whereKeyNot($consultation->id))
                ->latest('consulted_at')
                ->limit(5)
                ->get(),
        ];
    }

    private function redirectAfterSaving(ConsultationRequest $request, Consultation $consultation, string $message): RedirectResponse
    {
        $route = $request->wantsPrescription() && $consultation->prescriptionItems()->exists()
            ? redirect()->route('consultations.prescription', $consultation)
            : redirect()->route('consultations.show', $consultation);

        return $route->with('status', $message);
    }
}
