<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\Gender;
use App\Enums\Role;
use App\Http\Requests\PatientRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Services\AccountInvitations;
use Illuminate\Database\Eloquent\Builder;
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
        $gender = Gender::tryFrom((string) $request->query('genre'));
        $sort = $request->query('tri') === 'visite' ? 'visite' : 'nom';

        $patients = Patient::query()
            ->with('user')
            // Date de la dernière consultation (rendez-vous terminé), calculée par la base.
            ->withMax(['appointments as last_visit_at' => fn ($q) => $q->where('status', AppointmentStatus::Completed)], 'starts_at')
            ->search($search)
            ->when($gender, fn ($q) => $q->where('gender', $gender))
            ->when($sort === 'visite', fn ($q) => $q->orderByDesc('last_visit_at'), fn ($q) => $q->orderByName())
            ->paginate(self::PER_PAGE)
            ->withQueryString(); // garde la recherche et les filtres dans les liens de pagination

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
            'gender' => $gender,
            'sort' => $sort,
            'genders' => Gender::cases(),
            'total' => Patient::count(),
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

    public function store(PatientRequest $request, AccountInvitations $invitations): RedirectResponse
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

        $status = "{$patient->full_name} a été ajouté(e).";

        // Case « Créer l'accès à l'espace patient » : e-mail pour choisir son mot de passe.
        if ($request->boolean('portal_access') && $invitations->send($patient->user)) {
            $status .= " Un e-mail l'invite à choisir son mot de passe pour l'espace patient.";
        }

        // Bouton « Enregistrer et donner un rendez-vous » (maquette 9).
        if ($request->input('next') === 'appointment') {
            return redirect()->route('appointments.create', ['patient' => $patient->id])->with('status', $status);
        }

        return redirect()->route('patients.show', $patient)->with('status', $status);
    }

    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $patient->load('user');
        $user = request()->user();

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
            // Historique des consultations, tous médecins : médecins uniquement (RG-21).
            'consultations' => $user->can('viewAny', Consultation::class)
                ? Consultation::with(['appointment.doctor.user', 'prescriptionItems'])
                    ->whereHas('appointment', fn (Builder $q) => $q->where('patient_id', $patient->id))
                    ->latest('consulted_at')
                    ->get()
                : collect(),
            // Rendez-vous du jour que le médecin connecté peut transformer en consultation.
            'startable' => $user->isDoctor()
                ? $patient->appointments()->where('doctor_id', $user->doctor?->id)->onDate(today())
                    ->get()->first(fn (Appointment $a) => $user->can('create', [Consultation::class, $a]))
                : null,
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

    /**
     * Envoie au patient l'e-mail pour choisir son mot de passe : il accède alors
     * à son espace (patient inscrit au cabinet avant d'avoir un compte en ligne).
     */
    public function invite(Patient $patient, AccountInvitations $invitations): RedirectResponse
    {
        Gate::authorize('update', $patient);

        if (! $invitations->send($patient->user)) {
            return back()->with('error', 'Ajoutez d\'abord l\'adresse e-mail du patient.');
        }

        return back()->with('status', "Un e-mail d'accès à l'espace patient a été envoyé à {$patient->user->email}.");
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
