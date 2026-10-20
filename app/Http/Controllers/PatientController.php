<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Fiches patients (partie administrative). Le dossier médical a son propre
 * contrôleur (MedicalRecordController), réservé au médecin.
 */
class PatientController extends Controller
{
    /** Nombre de patients par page dans la liste. */
    public const PER_PAGE = 15;

    /** Nombre de rendez-vous passés affichés sur la fiche. */
    public const PAST_APPOINTMENTS = 10;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Patient::class);

        $search = trim((string) $request->query('q'));

        $patients = Patient::query()
            ->search($search)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString(); // garde ?q=... dans les liens de pagination

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Patient::class);

        return view('patients.create', [
            'patient' => new Patient,
            'genders' => Gender::cases(),
        ]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        // La fiche et son dossier médical (vide) sont créés ensemble :
        // si l'un échoue, rien n'est enregistré (transaction).
        $patient = DB::transaction(function () use ($request) {
            $patient = Patient::create($request->validated());
            $patient->medicalRecord()->create();

            return $patient;
        });

        return redirect()->route('patients.show', $patient)
            ->with('status', "{$patient->full_name} a été ajouté(e).");
    }

    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        // Rendez-vous en lecture seule (leur gestion viendra avec le module Rendez-vous).
        return view('patients.show', [
            'patient' => $patient,
            'upcoming' => $patient->appointments()->with('doctor')
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->get(),
            'past' => $patient->appointments()->with('doctor')
                ->where('starts_at', '<', now())
                ->orderByDesc('starts_at')
                ->limit(self::PAST_APPOINTMENTS)
                ->get(),
        ]);
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('update', $patient);

        return view('patients.edit', [
            'patient' => $patient,
            'genders' => Gender::cases(),
        ]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('status', "La fiche de {$patient->full_name} a été modifiée.");
    }
}
