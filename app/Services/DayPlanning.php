<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * « Planning du jour » du secrétariat (maquette 7, BF-15) : une ligne par
 * horaire, une colonne par médecin.
 */
class DayPlanning
{
    public function __construct(private readonly SlotFinder $slots) {}

    /**
     * Résultat :
     *   times : horaires des lignes, ex. ['08:00', '08:30', …]
     *   cells : [id du médecin][horaire] => ['state' => état du créneau (Slot::FREE…) ou null,
     *                                        'appointment' => rendez-vous ou null]
     *
     * @param  Collection<int, Doctor>  $doctors
     * @return array{times: Collection<int, string>, cells: array<int, array<string, array{state: ?string, appointment: ?Appointment}>>}
     */
    public function for(Collection $doctors, CarbonImmutable $date): array
    {
        $times = collect();
        $cells = [];

        // Créneaux de chaque médecin (disponibilités du jour).
        foreach ($doctors as $doctor) {
            foreach ($this->slots->slotsOn($doctor, $date) as $slot) {
                $cells[$doctor->id][$slot->label()] = ['state' => $slot->state, 'appointment' => null];
                $times->push($slot->label());
            }
        }

        // Rendez-vous du jour, annulés compris (maquette : « Annulé »).
        $appointments = Appointment::query()
            ->with('patient.user')
            ->whereIn('doctor_id', $doctors->modelKeys())
            ->onDate($date)
            ->orderBy('starts_at')
            ->get();

        foreach ($appointments as $appointment) {
            $time = $appointment->starts_at->format('H:i');
            $current = $cells[$appointment->doctor_id][$time]['appointment'] ?? null;

            // Un rendez-vous actif passe avant un rendez-vous annulé au même horaire.
            if ($current === null || ! $current->isActive()) {
                $cells[$appointment->doctor_id][$time] = [
                    'state' => $cells[$appointment->doctor_id][$time]['state'] ?? null,
                    'appointment' => $appointment,
                ];
            }

            $times->push($time);
        }

        return ['times' => $times->unique()->sort()->values(), 'cells' => $cells];
    }
}
