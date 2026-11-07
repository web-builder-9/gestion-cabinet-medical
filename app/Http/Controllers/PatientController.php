<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\Role;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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
            ->with('user')
            ->search($search)
            ->orderByName()
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
            'patient' => (new Patient)->setRelation('user', new User),
            'genders' => Gender::cases(),
        ]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        // Le compte (users), le dossier (patients) et le dossier médical vide sont
        // créés ensemble : si l'un échoue, rien n'est enregistré (transaction).
        $patient = DB::transaction(function () use ($request) {
            $account = new User($request->accountData());
            $account->role = Role::Patient;
            // Mot de passe aléatoire : le patient choisira le sien s'il obtient un accès en ligne.
            $account->password = Str::password(32);
            $account->save();

            $patient = $account->patient()->create([
                ...$request->patientData(),
                'data_consent' => true,
                'consent_given_at' => now(),
            ]);
            $patient->medicalRecord()->create();

            return $patient;
        });

        return redirect()->route('patients.show', $patient)
            ->with('status', "{$patient->full_name} a été ajouté(e).");
    }

    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $patient->load('user');

        // Rendez-vous en lecture seule (leur gestion viendra avec le module Rendez-vous).
        return view('patients.show', [
            'patient' => $patient,
            // Affiché seulement si la MedicalRecordPolicy l'autorise (@can dans la vue).
            'record' => $patient->medicalRecord ?? $patient->medicalRecord()->make(),
            'upcoming' => $patient->appointments()->with('doctor.user')
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->get(),
            'past' => $patient->appointments()->with('doctor.user')
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
            'patient' => $patient->load('user'),
            'genders' => Gender::cases(),
        ]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        DB::transaction(function () use ($request, $patient) {
            $patient->user->update($request->accountData());
            $patient->update($request->patientData());
        });

        return redirect()->route('patients.show', $patient)
            ->with('status', "La fiche de {$patient->full_name} a été modifiée.");
    }

    /**
     * Suppression douce : la fiche disparaît de la liste mais reste en base
     * (colonne deleted_at), avec son dossier et ses rendez-vous.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('delete', $patient);

        // Archivage (RG-22) : la fiche est masquée et l'accès en ligne coupé (RG-02).
        DB::transaction(function () use ($patient) {
            $patient->delete();
            $patient->user->forceFill(['is_active' => false])->save();
        });

        return redirect()->route('patients.index')
            ->with('status', "{$patient->full_name} a été archivé(e). Le médecin peut restaurer la fiche depuis les archives.");
    }

    public function archived(): View
    {
        Gate::authorize('viewArchived', Patient::class);

        return view('patients.archived', [
            'patients' => Patient::onlyTrashed()->with('user')->orderByDesc('deleted_at')->paginate(self::PER_PAGE),
        ]);
    }

    public function restore(Patient $patient): RedirectResponse
    {
        Gate::authorize('restore', $patient);

        DB::transaction(function () use ($patient) {
            $patient->restore();
            $patient->user->forceFill(['is_active' => true])->save();
        });

        return redirect()->route('patients.show', $patient)
            ->with('status', "La fiche de {$patient->full_name} a été restaurée.");
    }
}
