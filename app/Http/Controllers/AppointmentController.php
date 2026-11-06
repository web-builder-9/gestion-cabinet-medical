<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\Booking;
use App\Services\Slot;
use App\Services\SlotFinder;
use App\Support\DateParam;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rendez-vous gérés par le personnel : liste, prise (BF-13), fiche avec
 * historique (BF-30) et déplacement (BF-14). Les changements d'état
 * (confirmer, annuler, absent) sont dans AppointmentStatusController.
 */
class AppointmentController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly SlotFinder $slots,
        private readonly Booking $booking,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Appointment::class);

        $period = in_array($request->query('periode'), ['aujourdhui', 'a-venir', 'passes'], true) ? $request->query('periode') : 'a-venir';
        $status = AppointmentStatus::tryFrom((string) $request->query('etat'));
        $doctorId = $request->integer('medecin') ?: null;
        $search = trim((string) $request->query('q'));

        $appointments = Appointment::query()
            ->with(['patient.user', 'doctor.user'])
            ->when($period === 'aujourdhui', fn (Builder $q) => $q->onDate(today()))
            ->when($period === 'a-venir', fn (Builder $q) => $q->upcoming())
            ->when($period === 'passes', fn (Builder $q) => $q->past())
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($doctorId, fn (Builder $q) => $q->where('doctor_id', $doctorId))
            ->when($search !== '', fn (Builder $q) => $q->whereHas('patient', fn (Builder $p) => $p->search($search)))
            ->orderBy('starts_at', $period === 'passes' ? 'desc' : 'asc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'doctors' => $this->doctors(),
            'statuses' => AppointmentStatus::cases(),
            'period' => $period,
            'status' => $status,
            'doctorId' => $doctorId,
            'search' => $search,
            'pendingCount' => Appointment::where('status', AppointmentStatus::Pending)->upcoming()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Appointment::class);

        return view('appointments.form', $this->formData($request));
    }

    /**
     * RG-15 : un rendez-vous pris par le secrétariat est créé « Confirmé ».
     */
    public function store(AppointmentRequest $request): RedirectResponse
    {
        $slot = $this->booking->slotAt($request->doctor(), $request->start());

        $appointment = Appointment::create([
            'patient_id' => $request->integer('patient_id'),
            'doctor_id' => $request->integer('doctor_id'),
            'starts_at' => $slot->start,
            'ends_at' => $slot->end,
            'reason' => $request->validated('reason'),
            'notes' => $request->validated('notes'),
            'status' => AppointmentStatus::Confirmed,
        ]);

        return redirect()->route('appointments.show', $appointment)
            ->with('status', "Rendez-vous enregistré : {$this->describe($appointment)}.");
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);

        $appointment->load(['patient.user', 'doctor.user', 'doctor.specialty', 'statusChanges.author']);

        return view('appointments.show', ['appointment' => $appointment]);
    }

    /**
     * Déplacer : même formulaire que la prise de rendez-vous, patient fixé.
     */
    public function edit(Request $request, Appointment $appointment): View
    {
        Gate::authorize('update', $appointment);

        return view('appointments.form', $this->formData($request, $appointment));
    }

    /**
     * L'état ne change pas : une demande en attente déplacée reste à confirmer.
     */
    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $slot = $this->booking->slotAt($request->doctor(), $request->start(), $appointment->id);

        $appointment->update([
            'doctor_id' => $request->integer('doctor_id'),
            'starts_at' => $slot->start,
            'ends_at' => $slot->end,
            'reason' => $request->validated('reason'),
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('appointments.show', $appointment)
            ->with('status', "Rendez-vous déplacé : {$this->describe($appointment)}.");
    }

    /**
     * Données du formulaire de prise ou de déplacement (maquette 10).
     * Le médecin et la date se choisissent d'abord (rechargement de la page),
     * puis le créneau parmi les créneaux libres de ce jour.
     *
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?Appointment $appointment = null): array
    {
        $doctors = $this->doctors();
        $doctor = $doctors->firstWhere('id', $request->integer('medecin'))
            ?? $doctors->firstWhere('id', $appointment?->doctor_id)
            ?? $doctors->first();

        $patient = $appointment?->patient ?? Patient::with('user')->find($request->integer('patient'));
        $search = trim((string) $request->query('q'));

        $date = DateParam::from($request->query('date'))
            ?? $appointment?->starts_at->startOfDay()
            ?? ($doctor ? $this->slots->nextFreeSlot($doctor)?->start->startOfDay() : null)
            ?? CarbonImmutable::today();

        $daySlots = $doctor ? $this->slots->slotsOn($doctor, $date, $appointment?->id) : collect();
        $horizon = $this->slots->bookingHorizon();

        // Après un refus (créneau pris…), on propose les créneaux libres les plus proches.
        $requested = session()->getOldInput('time');
        $suggestions = $doctor && $requested && session('errors')?->has('time')
            ? $this->booking->nearbyFreeSlots($doctor, CarbonImmutable::parse($date->format('Y-m-d').' '.$requested), 3, $appointment?->id)
            : collect();

        return [
            'appointment' => $appointment,
            'patient' => $patient,
            'search' => $search,
            'patientResults' => ! $patient && $search !== ''
                ? Patient::with('user')->search($search)->limit(8)->get()
                : collect(),
            'doctors' => $doctors,
            'doctor' => $doctor,
            'date' => $date,
            'horizon' => $horizon,
            'freeSlots' => $daySlots->filter(fn (Slot $slot) => $slot->isFree() && $slot->start->lte($horizon))->values(),
            'daySlots' => $daySlots,
            'dayAppointments' => $doctor ? $this->appointmentsOf($doctor, $date) : collect(),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Médecins en activité, triés par nom.
     *
     * @return Collection<int, Doctor>
     */
    private function doctors(): Collection
    {
        return Doctor::activeByName();
    }

    /**
     * Rendez-vous actifs du médecin ce jour-là, par horaire (« 10:30 » => rendez-vous).
     *
     * @return Collection<string, Appointment>
     */
    private function appointmentsOf(Doctor $doctor, CarbonImmutable $date): Collection
    {
        return $doctor->appointments()->with('patient.user')->active()->onDate($date)->get()
            ->keyBy(fn (Appointment $appointment) => $appointment->starts_at->format('H:i'));
    }

    /**
     * « jeudi 5 novembre à 10:30 avec Dr Salma Ben Ali ».
     */
    private function describe(Appointment $appointment): string
    {
        $appointment->loadMissing('doctor.user');

        return $appointment->starts_at->translatedFormat('l j F').' à '.$appointment->starts_at->format('H:i')
            .' avec '.$appointment->doctor->display_name;
    }
}
