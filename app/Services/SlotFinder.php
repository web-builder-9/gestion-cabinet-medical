<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\Unavailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Calcul des créneaux d'un médecin (RG-11) :
 *   créneaux proposés = disponibilités hebdomadaires
 *                       − indisponibilités ponctuelles
 *                       − créneaux déjà réservés (rendez-vous actifs, RG-12).
 */
class SlotFinder
{
    /**
     * Tous les créneaux du médecin entre deux dates (incluses), avec leur état.
     *
     * $ignoreAppointmentId : lors d'un déplacement, le créneau actuel du rendez-vous
     * déplacé doit apparaître libre.
     *
     * @return Collection<int, Slot>
     */
    public function slotsBetween(
        Doctor $doctor,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $ignoreAppointmentId = null,
    ): Collection {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        // Trois requêtes pour toute la période, quel que soit le nombre de jours.
        $availabilities = $doctor->availabilities()->orderBy('start_time')->get()->groupBy(
            fn ($availability) => $availability->day_of_week->value
        );
        $unavailabilities = Unavailability::query()
            ->where('doctor_id', $doctor->id)
            ->where('starts_at', '<', $to)->where('ends_at', '>', $from)
            ->get();
        $appointments = Appointment::query()
            ->where('doctor_id', $doctor->id)->active()->during($from, $to)
            ->when($ignoreAppointmentId, fn ($q) => $q->whereKeyNot($ignoreAppointmentId))
            ->get(['starts_at', 'ends_at']);

        $now = CarbonImmutable::now();
        $slots = collect();

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $weekday = DayOfWeek::fromDate($day);

            foreach ($availabilities->get($weekday?->value, []) as $availability) {
                $end = $availability->endsOn($day);

                for ($start = $availability->startsOn($day); $start->addMinutes($availability->slot_minutes)->lte($end); $start = $start->addMinutes($availability->slot_minutes)) {
                    $slotEnd = $start->addMinutes($availability->slot_minutes);

                    $state = match (true) {
                        $start->lt($now) => Slot::PAST,
                        self::overlapsAny($unavailabilities, $start, $slotEnd) => Slot::UNAVAILABLE,
                        self::overlapsAny($appointments, $start, $slotEnd) => Slot::TAKEN,
                        default => Slot::FREE,
                    };

                    $slots->push(new Slot($start, $slotEnd, $state));
                }
            }
        }

        return $slots;
    }

    /**
     * Créneaux d'une journée.
     *
     * @return Collection<int, Slot>
     */
    public function slotsOn(Doctor $doctor, CarbonImmutable $date, ?int $ignoreAppointmentId = null): Collection
    {
        return $this->slotsBetween($doctor, $date, $date, $ignoreAppointmentId);
    }

    /**
     * Prochain créneau libre dans la limite de réservation (RG-14), ou null.
     */
    public function nextFreeSlot(Doctor $doctor, ?CarbonImmutable $from = null): ?Slot
    {
        $from ??= CarbonImmutable::now();
        $horizon = $this->bookingHorizon();

        // Recherche par semaines, pour ne pas calculer 60 jours d'un coup.
        for ($start = $from->startOfDay(); $start->lte($horizon); $start = $start->addWeek()) {
            $end = $start->addDays(6)->min($horizon);
            $slot = $this->slotsBetween($doctor, $start, $end)->first(fn (Slot $slot) => $slot->isFree());

            if ($slot) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Le créneau [start, end[ peut-il être réservé ? Il doit correspondre exactement
     * à un créneau libre du médecin (disponibilités, indisponibilités, RG-12)
     * et ne pas dépasser la limite de réservation (RG-14).
     */
    public function isBookable(Doctor $doctor, CarbonImmutable $start, CarbonImmutable $end, ?int $ignoreAppointmentId = null): bool
    {
        if ($start->gt($this->bookingHorizon())) {
            return false;
        }

        return $this->slotsOn($doctor, $start, $ignoreAppointmentId)->contains(
            fn (Slot $slot) => $slot->isFree() && $slot->start->eq($start) && $slot->end->eq($end)
        );
    }

    /**
     * Dernier jour réservable : aujourd'hui + délai de réservation (RG-14, 60 jours par défaut).
     */
    public function bookingHorizon(): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays(Setting::current()->booking_days_ahead)->endOfDay();
    }

    /**
     * @param  Collection<int, object{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>  $periods
     */
    private static function overlapsAny(Collection $periods, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $periods->contains(fn ($period) => $period->starts_at->lt($end) && $period->ends_at->gt($start));
    }
}
