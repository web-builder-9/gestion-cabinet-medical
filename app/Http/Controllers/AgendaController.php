<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\WeekAgenda;
use App\Support\DateParam;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Agenda par jour ou par semaine (maquette 11) : le médecin voit le sien (BF-19),
 * la secrétaire choisit le médecin (planning hebdomadaire, BF-15).
 */
class AgendaController extends Controller
{
    public function __invoke(Request $request, WeekAgenda $agenda): View
    {
        Gate::authorize('viewAny', Appointment::class);

        $user = $request->user();
        $doctors = Doctor::activeByName();

        // Un médecin ne voit que son propre agenda.
        $doctor = $user->isDoctor()
            ? $user->doctor
            : ($doctors->firstWhere('id', $request->integer('medecin')) ?? $doctors->first());

        abort_if($doctor === null, 404);

        $view = $request->query('vue') === 'jour' ? 'jour' : 'semaine';
        $date = DateParam::from($request->query('date')) ?? CarbonImmutable::today();

        // Semaine : du lundi au samedi (le cabinet est fermé le dimanche).
        $days = $view === 'jour'
            ? [$date]
            : array_map(fn (int $i) => $date->startOfWeek()->addDays($i), range(0, 5));

        return view('agenda.index', [
            'doctor' => $doctor->loadMissing('user', 'specialty'),
            'doctors' => $doctors,
            'view' => $view,
            'date' => $date,
            'days' => $days,
            'agenda' => $agenda->for($doctor, $days),
            'previous' => $view === 'jour' ? $this->skipSunday($date->subDay(), -1) : $date->subWeek(),
            'next' => $view === 'jour' ? $this->skipSunday($date->addDay(), 1) : $date->addWeek(),
        ]);
    }

    /**
     * Le cabinet est fermé le dimanche : on passe au jour suivant (+1) ou précédent (-1).
     */
    private function skipSunday(CarbonImmutable $date, int $direction): CarbonImmutable
    {
        return $date->isSunday() ? $date->addDays($direction) : $date;
    }
}
