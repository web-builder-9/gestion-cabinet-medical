<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Http\Requests\Doctor\AvailabilityRequest;
use App\Models\Availability;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * « Mes disponibilités » (maquette 12) : semaine type du médecin (BF-17)
 * et ses indisponibilités ponctuelles (BF-18, voir UnavailabilityController).
 */
class AvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Availability::class);

        $doctor = $request->user()->doctor;

        return view('availabilities.index', [
            'availabilities' => $doctor->availabilities()->orderBy('day_of_week')->orderBy('start_time')->get(),
            'unavailabilities' => $doctor->unavailabilities()->currentOrUpcoming()->orderBy('starts_at')->get(),
            ...$this->formOptions(),
        ]);
    }

    public function store(AvailabilityRequest $request): RedirectResponse
    {
        $availability = $request->user()->doctor->availabilities()->create($request->validated());

        return redirect()->route('availabilities.index')
            ->with('status', "La plage « {$availability->label()} » a été ajoutée.");
    }

    public function edit(Availability $availability): View
    {
        Gate::authorize('update', $availability);

        return view('availabilities.edit', [
            'availability' => $availability,
            ...$this->formOptions(),
        ]);
    }

    public function update(AvailabilityRequest $request, Availability $availability): RedirectResponse
    {
        $availability->update($request->validated());

        return redirect()->route('availabilities.index')
            ->with('status', "La plage « {$availability->label()} » a été modifiée.");
    }

    /**
     * Les rendez-vous déjà pris ne sont pas touchés : seuls les créneaux
     * proposés ensuite changent.
     */
    public function destroy(Availability $availability): RedirectResponse
    {
        Gate::authorize('delete', $availability);

        $availability->delete();

        return redirect()->route('availabilities.index')
            ->with('status', "La plage « {$availability->label()} » a été supprimée.");
    }

    /**
     * Listes des formulaires : jours, durées de créneau et horaires du cabinet.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'days' => DayOfWeek::cases(),
            'slotDurations' => SettingRequest::SLOT_DURATIONS,
            'settings' => Setting::current(),
        ];
    }
}
