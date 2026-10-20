<?php

namespace App\Http\Controllers;

use App\Enums\BloodType;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dossier médical d'un patient : données de santé, réservées au médecin
 * (middleware role:doctor sur les routes + MedicalRecordPolicy).
 */
class MedicalRecordController extends Controller
{
    public function edit(Patient $patient): View
    {
        $record = $patient->medicalRecord ?? $patient->medicalRecord()->make();

        Gate::authorize('update', $record);

        return view('patients.medical-record.edit', [
            'patient' => $patient,
            'record' => $record,
            'bloodTypes' => BloodType::cases(),
        ]);
    }

    public function update(UpdateMedicalRecordRequest $request, Patient $patient): RedirectResponse
    {
        $request->record()->fill($request->validated())->save();

        return redirect()->route('patients.show', $patient)
            ->with('status', 'Le dossier médical a été mis à jour.');
    }
}
