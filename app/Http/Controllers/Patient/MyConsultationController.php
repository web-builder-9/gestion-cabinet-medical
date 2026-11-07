<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mes comptes rendus » : les consultations du patient, en lecture seule (BF-11, RG-21).
 */
class MyConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        return view('my-consultations.index', [
            'consultations' => Consultation::with(['appointment.doctor.user', 'appointment.doctor.specialty', 'prescriptionItems'])
                ->whereHas('appointment', fn (Builder $q) => $q->where('patient_id', $patient->id))
                ->latest('consulted_at')
                ->paginate(10),
        ]);
    }
}
