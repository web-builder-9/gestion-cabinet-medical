<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\UnavailabilityRequest;
use App\Models\Unavailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Indisponibilités ponctuelles du médecin (BF-18) : congé, formation…
 * Les créneaux de la période ne sont plus proposés (RG-11).
 */
class UnavailabilityController extends Controller
{
    public function store(UnavailabilityRequest $request): RedirectResponse
    {
        $unavailability = $request->user()->doctor->unavailabilities()->create($request->validated());

        $redirect = redirect()->route('availabilities.index')
            ->with('status', "Indisponibilité déclarée : {$unavailability->periodLabel()}.");

        // Les rendez-vous déjà pris sur la période restent à déplacer par le secrétariat,
        // qui les voit sur son tableau de bord.
        $conflicts = $unavailability->conflictingAppointments()->count();

        if ($conflicts > 0) {
            $redirect->with('warning', $conflicts === 1
                ? '1 rendez-vous actif tombe sur cette période : le secrétariat sera prévenu pour le déplacer.'
                : "{$conflicts} rendez-vous actifs tombent sur cette période : le secrétariat sera prévenu pour les déplacer.");
        }

        return $redirect;
    }

    public function destroy(Unavailability $unavailability): RedirectResponse
    {
        Gate::authorize('delete', $unavailability);

        $unavailability->delete();

        return redirect()->route('availabilities.index')
            ->with('status', "L'indisponibilité du {$unavailability->periodLabel()} a été retirée.");
    }
}
