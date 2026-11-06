<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\Slot;
use App\Services\SlotFinder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Agenda de démonstration : du 30e jour passé au 14e jour à venir.
 *
 * Les rendez-vous sont placés uniquement sur des créneaux libres calculés par
 * SlotFinder, donc sans conflit (RG-12, RG-13). Chaque rendez-vous suit un vrai
 * parcours d'états (RG-17), daté et attribué à son auteur, ce qui remplit
 * l'historique (RG-23) ; un rendez-vous terminé a sa consultation (RG-18, RG-19).
 */
class AppointmentSeeder extends Seeder
{
    /**
     * Cas cliniques fictifs : motif, observations, diagnostic, recommandations, ordonnance.
     */
    private const CASES = [
        ['Toux persistante', 'Toux sèche depuis une semaine, pas de fièvre.', 'Bronchite aiguë',
            'Repos, bien s\'hydrater ; revoir si la toux persiste au-delà de 10 jours.',
            [['Sirop antitussif', null, '1 cuillère le soir', '5 jours'], ['Paracétamol', '1 g', '1 comprimé 3 fois par jour si fièvre', '5 jours']]],
        ['Fièvre', 'Température 38,5 °C, gorge rouge, pas de plaques.', 'Angine virale',
            'Repos 3 jours.', [['Paracétamol', '1 g', '1 comprimé toutes les 6 heures si fièvre', '3 jours']]],
        ['Suivi tension artérielle', 'TA 14/9 au repos, contrôlée deux fois.', 'Hypertension modérée',
            'Réduire le sel ; marche 30 minutes par jour ; contrôle dans un mois.',
            [['Amlodipine', '5 mg', '1 comprimé le matin', '3 mois']]],
        ['Douleurs abdominales', 'Abdomen souple, douleur épigastrique après les repas.', 'Gastrite',
            'Éviter épices, café et repas copieux.', [['Oméprazole', '20 mg', '1 gélule avant le petit-déjeuner', '4 semaines']]],
        ['Contrôle', 'Examen clinique normal.', 'Bon état général', 'Contrôle annuel.', []],
        ['Certificat médical', 'Examen clinique normal, pas de contre-indication.', 'Apte à la pratique sportive', null, []],
        ['Bilan annuel', 'Poids stable, TA 12/8.', 'Bilan normal', 'Prise de sang de contrôle dans un an.', []],
    ];

    private SlotFinder $slots;

    private User $secretary;

    /** Rendez-vous déjà placés par patient, pour éviter qu'un patient soit à deux endroits (RG-13). */
    private array $busy = [];

    public function run(): void
    {
        $this->slots = app(SlotFinder::class);
        $this->secretary = User::where('email', 'secretaire@cabinet.test')->firstOrFail();

        $doctors = Doctor::with('user', 'specialty')->get()->keyBy(fn (Doctor $doctor) => $doctor->user->email);

        $this->seedAmira($doctors['medecin@cabinet.test'], $doctors['l.ammar@cabinet.test']);

        $amira = Patient::whereHas('user', fn ($q) => $q->where('email', 'patient@cabinet.test'))->firstOrFail();
        $patients = Patient::with('user')->whereKeyNot($amira->id)->get();

        foreach ($doctors as $doctor) {
            $this->seedAgenda($doctor, $patients);
        }

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        Auth::forgetUser();
    }

    /**
     * Parcours de la patiente de démonstration (maquettes 6 et 13).
     */
    private function seedAmira(Doctor $benAli, Doctor $ammar): void
    {
        $amira = Patient::whereHas('user', fn ($q) => $q->where('email', 'patient@cabinet.test'))->with('user')->firstOrFail();
        $today = CarbonImmutable::today();

        $this->book($amira, $ammar, $this->slotNear($ammar, $today->subDays(60), '15:00'), 'Électrocardiogramme', AppointmentStatus::NoShow, online: true);
        $this->book($amira, $benAli, $this->slotNear($benAli, $today->subDays(45), '09:00'), 'Contrôle asthme', AppointmentStatus::Cancelled, online: true);

        $bronchitis = $this->book($amira, $benAli, $this->slotNear($benAli, $today->subDays(25), '09:30'), 'Toux persistante', AppointmentStatus::Completed, online: true, case: false);
        $this->writeConsultation($bronchitis, [
            'Toux persistante depuis 10 jours',
            'Toux sèche nocturne, pas de fièvre. Auscultation : quelques sibilants à droite. Saturation 98 %.',
            'Bronchite aiguë',
            'Éviter la fumée et l\'effort intense ; revoir si la toux persiste au-delà de 10 jours.',
            [['Salbutamol inhalé', '100 µg', '2 bouffées en cas de gêne, jusqu\'à 4 fois par jour', '10 jours'],
                ['Sirop antitussif', null, '1 cuillère le soir', '5 jours']],
        ]);

        $this->book($amira, $benAli, $this->slotNear($benAli, $today->addDays(3), '10:30'), 'Contrôle après bronchite', AppointmentStatus::Pending, online: true);
        $this->book($amira, $ammar, $this->slotNear($ammar, $today->addDays(11), '09:00'), 'Bilan cardiaque', AppointmentStatus::Confirmed, online: false);
    }

    /**
     * Remplit l'agenda d'un médecin : 3 à 6 rendez-vous par jour travaillé.
     *
     * @param  Collection<int, Patient>  $patients
     */
    private function seedAgenda(Doctor $doctor, Collection $patients): void
    {
        $today = CarbonImmutable::today();
        $days = $this->slots->slotsBetween($doctor, $today->subDays(30), $today->addDays(14))
            ->reject(fn (Slot $slot) => in_array($slot->state, [Slot::TAKEN, Slot::UNAVAILABLE], true))
            ->groupBy(fn (Slot $slot) => $slot->start->toDateString());

        foreach ($days as $date => $daySlots) {
            $count = match (true) {
                $date === $today->toDateString() => 6,
                $date < $today->toDateString() => fake()->numberBetween(3, 6),
                default => fake()->numberBetween(1, 4),
            };

            foreach ($daySlots->shuffle()->take($count) as $slot) {
                $patient = $patients->shuffle()->first(fn (Patient $p) => $this->isFree($p, $slot));

                if ($patient === null) {
                    continue;
                }

                $online = $patient->user->email !== null && fake()->boolean(40);
                $this->book($patient, $doctor, $slot, null, $this->finalStatus($slot), $online);
            }
        }
    }

    /**
     * État final plausible : passé = terminé (surtout), absent ou annulé ;
     * à venir = confirmé (surtout) ou demande en attente.
     */
    private function finalStatus(Slot $slot): AppointmentStatus
    {
        if ($slot->start->isPast()) {
            return fake()->randomElement([
                ...array_fill(0, 8, AppointmentStatus::Completed),
                AppointmentStatus::NoShow,
                AppointmentStatus::Cancelled,
            ]);
        }

        return fake()->boolean(85) ? AppointmentStatus::Confirmed : AppointmentStatus::Pending;
    }

    /**
     * Crée un rendez-vous et lui fait suivre son parcours d'états, en datant
     * chaque étape (le temps est « déplacé » avec Carbon::setTestNow).
     *
     * - pris en ligne par le patient : créé « En attente » (RG-15), confirmé par la secrétaire ;
     * - pris par la secrétaire : créé « Confirmé » (RG-15).
     */
    private function book(
        Patient $patient,
        Doctor $doctor,
        Slot $slot,
        ?string $reason,
        AppointmentStatus $final,
        bool $online,
        bool $case = true,
    ): Appointment {
        $realNow = CarbonImmutable::now();
        $clinicalCase = fake()->randomElement(self::CASES);

        // Pris entre 1 et 14 jours avant le rendez-vous, et jamais dans le futur.
        $bookedAt = $slot->start->subDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(8, 19), fake()->numberBetween(0, 59));
        $bookedAt = $bookedAt->min($realNow->subHours(fake()->numberBetween(2, 30)));

        $this->actAs($online ? $patient->user : $this->secretary, $bookedAt);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $slot->start,
            'ends_at' => $slot->end,
            'reason' => $reason ?? $clinicalCase[0],
            'status' => $online ? AppointmentStatus::Pending : AppointmentStatus::Confirmed,
        ]);
        $this->busy[$patient->id][] = [$slot->start, $slot->end];

        if ($final === AppointmentStatus::Pending) {
            return $this->done($appointment);
        }

        if ($final === AppointmentStatus::Cancelled) {
            $this->actAs($online ? $patient->user : $this->secretary, $bookedAt->addHours(fake()->numberBetween(2, 20))->min($realNow));
            $appointment->changeStatus(AppointmentStatus::Cancelled);

            return $this->done($appointment);
        }

        if ($online) {
            $this->actAs($this->secretary, $bookedAt->addHours(fake()->numberBetween(1, 5))->min($slot->start)->min($realNow));
            $appointment->changeStatus(AppointmentStatus::Confirmed);
        }

        if ($final === AppointmentStatus::NoShow) {
            $this->actAs($this->secretary, $slot->end->addMinutes(10)->min($realNow));
            $appointment->changeStatus(AppointmentStatus::NoShow);
        }

        if ($final === AppointmentStatus::Completed) {
            $this->actAs($doctor->user, $slot->end->min($realNow));
            $appointment->changeStatus(AppointmentStatus::Completed);

            if ($case) {
                $this->writeConsultation($appointment, $clinicalCase);
            }
        }

        return $this->done($appointment);
    }

    /**
     * Compte rendu d'un rendez-vous terminé (RG-18, RG-19), avec son ordonnance.
     *
     * @param  array{0: string, 1: string, 2: string, 3: ?string, 4: list<array{0: string, 1: ?string, 2: string, 3: ?string}>}  $case
     */
    private function writeConsultation(Appointment $appointment, array $case): void
    {
        [$reason, $observations, $diagnosis, $treatment, $items] = $case;

        $consultation = $appointment->consultation()->create([
            'consulted_at' => $appointment->starts_at,
            'reason' => $reason,
            'observations' => $observations,
            'diagnosis' => $diagnosis,
            'treatment' => $treatment,
        ]);

        foreach (array_values($items) as $position => [$medication, $dosage, $posology, $duration]) {
            $consultation->prescriptionItems()->create(compact('position', 'medication', 'dosage', 'posology', 'duration'));
        }
    }

    /**
     * Premier créneau libre à l'heure voulue, en partant de la date indiquée.
     */
    private function slotNear(Doctor $doctor, CarbonImmutable $date, string $time): Slot
    {
        for ($day = $date; $day->lte($date->addDays(21)); $day = $day->addDay()) {
            $slot = $this->slots->slotsOn($doctor, $day)
                ->first(fn (Slot $slot) => $slot->label() === $time && in_array($slot->state, [Slot::FREE, Slot::PAST], true));

            if ($slot) {
                return $slot;
            }
        }

        throw new \RuntimeException("Aucun créneau à {$time} pour {$doctor->display_name}.");
    }

    private function isFree(Patient $patient, Slot $slot): bool
    {
        foreach ($this->busy[$patient->id] ?? [] as [$start, $end]) {
            if ($start->lt($slot->end) && $end->gt($slot->start)) {
                return false;
            }
        }

        return true;
    }

    /**
     * L'action suivante est faite par $user, à la date $at (auteur et date de l'historique).
     */
    private function actAs(User $user, CarbonImmutable $at): void
    {
        Carbon::setTestNow($at);
        CarbonImmutable::setTestNow($at);
        Auth::setUser($user);
    }

    private function done(Appointment $appointment): Appointment
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        return $appointment;
    }
}
