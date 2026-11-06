<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Setting;
use App\Models\Specialty;
use App\Services\SlotFinder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page d'accueil publique (maquette 1) : les médecins du cabinet, leur
 * spécialité et leur prochain créneau libre (BF-01, BF-02, BF-07).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, SlotFinder $slots): View
    {
        $search = trim((string) $request->query('q'));
        $specialty = Specialty::find($request->integer('specialite'));

        $doctors = Doctor::active()
            ->search($search)
            ->when($specialty, fn ($query) => $query->where('specialty_id', $specialty->id))
            ->with(['user', 'specialty'])
            ->get()
            ->sortBy(fn (Doctor $doctor) => $doctor->user->last_name)
            ->values();

        return view('home', [
            'doctors' => $doctors,
            'nextSlots' => $doctors->mapWithKeys(fn (Doctor $doctor) => [$doctor->id => $slots->nextFreeSlot($doctor)]),
            // Seules les spécialités qui ont un médecin en activité servent de filtre.
            'specialties' => Specialty::whereHas('doctors', fn ($q) => $q->active())->orderBy('name')->get(),
            'specialty' => $specialty,
            'search' => $search,
            'settings' => Setting::current(),
        ]);
    }
}
