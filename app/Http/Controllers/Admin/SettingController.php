<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Models\Specialty;
use App\Services\Statistics;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Page « Spécialités et paramètres » de l'administrateur (maquette 17) :
 * spécialités (BF-25), paramètres du cabinet (BF-26), activité du mois (BF-27).
 */
class SettingController extends Controller
{
    public function edit(Statistics $statistics): View
    {
        $settings = Setting::current();
        Gate::authorize('update', $settings);

        $month = CarbonImmutable::now();
        $monthName = $month->translatedFormat('F');

        return view('admin.settings', [
            'settings' => $settings,
            'specialties' => Specialty::withCount('doctors')->orderBy('name')->get(),
            // « Activité d'octobre », « Activité de novembre »
            'activityTitle' => 'Activité '.(in_array($monthName[0], ['a', 'o'], true) ? "d'" : 'de ').$monthName,
            'stats' => $statistics->forMonth($month),
            'slotDurations' => SettingRequest::SLOT_DURATIONS,
            'cancellationHours' => SettingRequest::CANCELLATION_HOURS,
            'bookingDays' => SettingRequest::BOOKING_DAYS,
            'reminderHours' => SettingRequest::REMINDER_HOURS,
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        // Une seule ligne de paramètres : créée au premier enregistrement.
        $settings = Setting::current();
        $settings->fill($request->validated())->save();

        return redirect()->route('admin.settings')->with('status', 'Les paramètres du cabinet ont été enregistrés.');
    }
}
