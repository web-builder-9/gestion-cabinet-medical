<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
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
}
