<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Ordonnance d'une consultation (BF-22, maquette 15) : aperçu imprimable et
 * PDF au format A5. Lisible par les médecins et par le patient concerné (RG-21).
 */
class PrescriptionController extends Controller
{
    public function show(Consultation $consultation): View
    {
        Gate::authorize('view', $consultation);

        return view('prescriptions.show', $this->data($consultation));
    }

    public function download(Consultation $consultation): Response
    {
        Gate::authorize('view', $consultation);

        return Pdf::loadView('prescriptions.pdf', $this->data($consultation))
            ->setPaper('a5')
            ->download($consultation->prescriptionNumber().'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Consultation $consultation): array
    {
        $consultation->load(['appointment.patient.user', 'appointment.doctor.user', 'appointment.doctor.specialty', 'prescriptionItems']);

        abort_if($consultation->prescriptionItems->isEmpty(), 404);

        return [
            'consultation' => $consultation,
            'patient' => $consultation->appointment->patient,
            'doctor' => $consultation->appointment->doctor,
            'settings' => Setting::current(),
        ];
    }
}
