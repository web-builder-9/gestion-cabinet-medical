<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\BookingRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Setting;
use App\Services\AppointmentNotifier;
use App\Services\Booking;
use App\Services\Slot;
use App\Services\SlotFinder;
use App\Support\DateParam;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Prise de rendez-vous en ligne par le patient (maquette 5) : 1. médecin,
 * 2. créneau, 3. confirmation (BF-07, BF-08) ; modification et annulation
 * jusqu'au délai fixé par le cabinet (BF-09, RG-16).
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly SlotFinder $slots,
        private readonly Booking $booking,
        private readonly AppointmentNotifier $notifier,
    ) {}

    public function create(Request $request): View
    {
        abort_unless($request->user()->patient, 403);

        $doctor = Doctor::active()->with(['user', 'specialty'])->find($request->integer('medecin'));

        // Étape 1 : choisir le médecin.
        if (! $doctor) {
            $doctors = Doctor::activeByName();

            return view('booking.doctors', [
                'doctors' => $doctors,
                'nextSlots' => $doctors->mapWithKeys(fn (Doctor $d) => [$d->id => $this->slots->nextFreeSlot($d)]),
            ]);
        }

        // Étape 2 : choisir le créneau.
        return view('booking.slots', $this->slotData($request, $doctor));
    }

    /**
     * RG-15 : une demande prise en ligne est créée « En attente ».
     */
    public function store(BookingRequest $request): RedirectResponse
    {
        $slot = $this->booking->slotAt($request->doctor(), $request->start());

        $appointment = Appointment::create([
            'patient_id' => $request->patient()->id,
            'doctor_id' => $request->doctor()->id,
            'starts_at' => $slot->start,
            'ends_at' => $slot->end,
            'reason' => $request->validated('reason'),
            'status' => AppointmentStatus::Pending,
        ]);

        // BF-28 : accusé de réception de la demande.
        $this->notifier->send($appointment, NotificationType::Confirmation);

        return redirect()->route('dashboard')
            ->with('status', "Demande envoyée pour {$this->when($slot->start)}. Le secrétariat va la confirmer ; vous serez prévenu(e) par e-mail.");
    }

    public function edit(Request $request, Appointment $appointment): View
    {
        Gate::authorize('update', $appointment);

        return view('booking.slots', $this->slotData($request, $appointment->doctor, $appointment));
    }

    /**
     * RG-16 : une modification par le patient remet le rendez-vous « En attente ».
     */
    public function update(BookingRequest $request, Appointment $appointment): RedirectResponse
    {
        $slot = $this->booking->slotAt($appointment->doctor, $request->start(), $appointment->id);

        $appointment->fill([
            'starts_at' => $slot->start,
            'ends_at' => $slot->end,
            'reason' => $request->validated('reason'),
        ]);

        if ($appointment->status === AppointmentStatus::Confirmed) {
            $appointment->changeStatus(AppointmentStatus::Pending); // enregistre aussi le nouveau créneau
        } else {
            $appointment->save();
        }

        if ($appointment->wasChanged('starts_at')) {
            $this->notifier->send($appointment, NotificationType::Modification); // BF-28
        }

        return redirect()->route('dashboard')
            ->with('status', "Rendez-vous déplacé au {$this->when($slot->start)}. Il est de nouveau en attente de confirmation.");
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        $appointment->changeStatus(AppointmentStatus::Cancelled);
        $this->notifier->send($appointment, NotificationType::Cancellation); // BF-28

        return redirect()->route('dashboard')
            ->with('status', "Votre rendez-vous du {$this->when($appointment->starts_at)} est annulé.");
    }

    /**
     * Données de l'étape « Créneau » : jours de la semaine, créneaux du jour choisi.
     *
     * @return array<string, mixed>
     */
    private function slotData(Request $request, Doctor $doctor, ?Appointment $appointment = null): array
    {
        $today = CarbonImmutable::today();
        $horizon = $this->slots->bookingHorizon();

        // Jour affiché : celui demandé, sinon celui du rendez-vous modifié, sinon le prochain jour libre.
        $day = DateParam::from($request->query('jour'))
            ?? $appointment?->starts_at->startOfDay()
            ?? $this->slots->nextFreeSlot($doctor)?->start->startOfDay()
            ?? $today;
        $day = $day->max($today)->min($horizon->startOfDay());

        $monday = $day->startOfWeek();
        $slots = $this->slots->slotsOn($doctor, $day, $appointment?->id)
            ->map(fn (Slot $slot) => ['slot' => $slot, 'bookable' => $slot->isFree() && $slot->start->lte($horizon)]);

        return [
            'doctor' => $doctor->loadMissing('user', 'specialty'),
            'appointment' => $appointment,
            'day' => $day,
            'week' => array_map(fn (int $i) => $monday->addDays($i), range(0, 5)),
            'previousWeek' => $monday->subWeek()->gte($today->startOfWeek()) ? $monday->subWeek() : null,
            'nextWeek' => $monday->addWeek()->lte($horizon) ? $monday->addWeek() : null,
            'today' => $today,
            'horizon' => $horizon,
            'morning' => $slots->filter(fn ($s) => $s['slot']->start->hour < 13)->values(),
            'afternoon' => $slots->filter(fn ($s) => $s['slot']->start->hour >= 13)->values(),
            'slotMinutes' => $doctor->availabilities()->value('slot_minutes'),
            'settings' => Setting::current(),
        ];
    }

    /**
     * « jeudi 5 novembre à 10:30 ».
     */
    private function when(CarbonImmutable $start): string
    {
        return $start->translatedFormat('l j F').' à '.$start->format('H:i');
    }
}
