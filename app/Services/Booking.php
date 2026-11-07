<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Règles de prise d'un rendez-vous, communes au secrétariat (BF-13, BF-14)
 * et à l'espace patient (BF-08, BF-09) :
 *   - le créneau fait partie des disponibilités du médecin, hors indisponibilités (RG-11) ;
 *   - il est libre : un seul rendez-vous actif par créneau (RG-12) ;
 *   - le patient n'a pas déjà un rendez-vous au même moment (RG-13) ;
 *   - il est dans le futur, au plus N jours à l'avance (RG-14).
 */
class Booking
{
    public function __construct(private readonly SlotFinder $slots) {}

    /**
     * Le créneau du médecin qui commence à $start (quel que soit son état), ou null.
     * $ignoreId : rendez-vous déplacé, dont le créneau actuel compte comme libre.
     */
    public function slotAt(Doctor $doctor, CarbonImmutable $start, ?int $ignoreId = null): ?Slot
    {
        return $this->slots->slotsOn($doctor, $start, $ignoreId)
            ->first(fn (Slot $slot) => $slot->start->eq($start));
    }

    /**
     * Pourquoi ce rendez-vous ne peut pas être pris, ou null s'il peut l'être.
     */
    public function problem(Doctor $doctor, Patient $patient, CarbonImmutable $start, ?Appointment $moved = null): ?string
    {
        $slot = $this->slotAt($doctor, $start, $moved?->id);

        if (! $slot) {
            return "{$start->format('H:i')} n'est pas un créneau de {$doctor->display_name} ce jour-là.";
        }

        if ($slot->state === Slot::PAST) {
            return 'Ce créneau est déjà passé : un rendez-vous se prend sur un créneau futur.';
        }

        if ($slot->state === Slot::UNAVAILABLE) {
            return "{$doctor->display_name} est indisponible sur ce créneau.";
        }

        if ($slot->state === Slot::TAKEN) {
            $taken = Appointment::overlapping($doctor->id, $slot->start, $slot->end, $moved?->id)->with('patient.user')->first();

            return "Ce créneau est déjà pris. {$taken->patient->full_name} a un rendez-vous "
                .mb_strtolower($taken->status->label())." de {$taken->starts_at->format('H:i')} à {$taken->ends_at->format('H:i')}"
                ." avec {$doctor->display_name}. Un créneau ne porte qu'un seul rendez-vous actif.";
        }

        if ($slot->start->gt($this->slots->bookingHorizon())) {
            return 'Un rendez-vous se prend au plus '.Setting::current()->booking_days_ahead.' jours à l\'avance.';
        }

        $other = Appointment::overlappingForPatient($patient->id, $slot->start, $slot->end, $moved?->id)->with('doctor.user')->first();

        if ($other) {
            return "{$patient->full_name} a déjà un rendez-vous de {$other->starts_at->format('H:i')} à {$other->ends_at->format('H:i')}"
                ." avec {$other->doctor->display_name} : un patient ne peut pas avoir deux rendez-vous qui se chevauchent.";
        }

        return null;
    }

    /**
     * Les créneaux libres du même jour les plus proches de $start (« Créneaux libres proches »).
     *
     * @return Collection<int, Slot>
     */
    public function nearbyFreeSlots(Doctor $doctor, CarbonImmutable $start, int $count = 3, ?int $ignoreId = null): Collection
    {
        $horizon = $this->slots->bookingHorizon();

        return $this->slots->slotsOn($doctor, $start, $ignoreId)
            ->filter(fn (Slot $slot) => $slot->isFree() && $slot->start->lte($horizon) && ! $slot->start->eq($start))
            ->sortBy(fn (Slot $slot) => abs($slot->start->diffInMinutes($start, true)))
            ->take($count)
            ->sortBy(fn (Slot $slot) => $slot->start)
            ->values();
    }
}
