<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\Unavailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matrice des droits : reprend le tableau « Rôles et droits d'accès » de CLAUDE.md.
 * Chaque ligne : [action, médecin, secrétaire, administrateur, patient] ; le patient
 * testé n'est pas celui de la fiche (ce cas est testé à part).
 */
class PolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = [
            'Médecin' => User::factory()->doctor()->create(),
            'Secrétaire' => User::factory()->secretary()->create(),
            'Administrateur' => User::factory()->admin()->create(),
            'Patient' => User::factory()->patient()->create(),
        ];
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function patientMatrix(): array
    {
        return [
            'voir la liste' => ['viewAny', true, true, false, false],
            'voir une fiche' => ['view', true, true, false, false],
            'créer une fiche' => ['create', true, true, false, false],
            'modifier une fiche' => ['update', true, true, false, false],
            'archiver une fiche' => ['delete', true, false, false, false],
            'voir les archives' => ['viewArchived', true, false, false, false],
            'restaurer une fiche' => ['restore', true, false, false, false],
            'supprimer pour de bon' => ['forceDelete', false, false, false, false],
        ];
    }

    #[DataProvider('patientMatrix')]
    public function test_patient_rights(string $ability, bool ...$expected): void
    {
        $target = in_array($ability, ['viewAny', 'create', 'viewArchived'], true) ? Patient::class : Patient::factory()->create();

        $this->assertRights($ability, $target, $expected);
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function medicalRecordMatrix(): array
    {
        return [
            'voir un dossier médical' => ['view', true, false, false, false],
            'créer un dossier médical' => ['create', true, false, false, false],
            'modifier un dossier médical' => ['update', true, false, false, false],
        ];
    }

    #[DataProvider('medicalRecordMatrix')]
    public function test_medical_record_rights(string $ability, bool ...$expected): void
    {
        $target = $ability === 'create' ? MedicalRecord::class : MedicalRecord::factory()->create();

        $this->assertRights($ability, $target, $expected);
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function appointmentMatrix(): array
    {
        return [
            'voir les rendez-vous' => ['viewAny', true, true, false, false],
            'voir un rendez-vous' => ['view', true, true, false, false],
            'créer un rendez-vous' => ['create', true, true, false, false],
            'modifier un rendez-vous' => ['update', true, true, false, false],
            'supprimer un rendez-vous' => ['delete', false, false, false, false],
        ];
    }

    #[DataProvider('appointmentMatrix')]
    public function test_appointment_rights(string $ability, bool ...$expected): void
    {
        $target = in_array($ability, ['viewAny', 'create'], true)
            ? Appointment::class
            : Appointment::factory()->at(now()->addDay())->confirmed()->create();

        $this->assertRights($ability, $target, $expected);
    }

    /**
     * RG-17 : les actions proposées suivent les transitions autorisées.
     */
    public function test_appointment_actions_follow_the_allowed_transitions(): void
    {
        $secretary = $this->users['Secrétaire'];
        $pending = Appointment::factory()->at(now()->addDay())->pending()->create();
        $confirmedPast = Appointment::factory()->at(now()->subHour())->confirmed()->create();
        $confirmedFuture = Appointment::factory()->at(now()->addDay())->confirmed()->create();
        $cancelled = Appointment::factory()->at(now()->addDay())->cancelled()->create();

        $this->assertTrue($secretary->can('confirm', $pending));
        $this->assertFalse($secretary->can('confirm', $confirmedFuture));
        $this->assertTrue($secretary->can('cancel', $pending));
        $this->assertTrue($secretary->can('markNoShow', $confirmedPast));
        $this->assertFalse($secretary->can('markNoShow', $confirmedFuture), 'Absent seulement une fois l\'heure passée.');
        $this->assertFalse($secretary->can('markNoShow', $pending));

        // Un état final ne change plus.
        foreach (['update', 'confirm', 'cancel', 'markNoShow'] as $ability) {
            $this->assertFalse($secretary->can($ability, $cancelled), $ability);
        }

        // Ni l'administrateur ni un patient ne gèrent les rendez-vous du cabinet.
        $this->assertFalse($this->users['Administrateur']->can('confirm', $pending));
        $this->assertFalse($this->users['Patient']->can('cancel', $pending));
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function staffMatrix(): array
    {
        return [
            'voir les comptes' => ['viewAny', false, false, true, false],
            'créer un compte' => ['create', false, false, true, false],
            'modifier un compte' => ['update', false, false, true, false],
            'changer un rôle' => ['updateRole', false, false, true, false],
            'désactiver ou réactiver un compte' => ['toggleActive', false, false, true, false],
            'supprimer un compte' => ['delete', false, false, false, false],
        ];
    }

    #[DataProvider('staffMatrix')]
    public function test_staff_account_rights(string $ability, bool ...$expected): void
    {
        $target = in_array($ability, ['viewAny', 'create'], true) ? User::class : User::factory()->secretary()->create();

        $this->assertRights($ability, $target, $expected);
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function specialtyMatrix(): array
    {
        return [
            'ajouter une spécialité' => ['create', false, false, true, false],
            'modifier une spécialité' => ['update', false, false, true, false],
            'supprimer une spécialité sans médecin' => ['delete', false, false, true, false],
        ];
    }

    #[DataProvider('specialtyMatrix')]
    public function test_specialty_rights(string $ability, bool ...$expected): void
    {
        $target = $ability === 'create' ? Specialty::class : Specialty::factory()->create();

        $this->assertRights($ability, $target, $expected);
    }

    public function test_a_specialty_given_to_a_doctor_cannot_be_deleted(): void
    {
        $specialty = Specialty::factory()->create();
        Doctor::factory()->for($specialty)->create();

        $this->assertFalse($this->users['Administrateur']->can('delete', $specialty));
    }

    public function test_only_the_admin_changes_the_settings(): void
    {
        $this->assertRights('update', Setting::current(), [false, false, true, false]);
    }

    public function test_nobody_can_deactivate_their_own_account(): void
    {
        $admin = $this->users['Administrateur'];

        $this->assertFalse($admin->can('toggleActive', $admin));
    }

    /**
     * @return array<string, array{string, bool, bool, bool, bool}>
     */
    public static function availabilityMatrix(): array
    {
        return [
            'voir ses disponibilités' => ['viewAny', true, false, false, false],
            'ajouter une plage' => ['create', true, false, false, false],
            'modifier sa plage' => ['update', true, false, false, false],
            'supprimer sa plage' => ['delete', true, false, false, false],
        ];
    }

    #[DataProvider('availabilityMatrix')]
    public function test_availability_rights(string $ability, bool ...$expected): void
    {
        $target = in_array($ability, ['viewAny', 'create'], true)
            ? Availability::class
            : Availability::factory()->for($this->users['Médecin']->doctor)->create();

        $this->assertRights($ability, $target, $expected);
    }

    public function test_a_doctor_manages_their_own_availabilities_and_unavailabilities_only(): void
    {
        $doctor = $this->users['Médecin'];

        $this->assertFalse($doctor->can('update', Availability::factory()->create()));
        $this->assertTrue($doctor->can('delete', Unavailability::factory()->for($doctor->doctor)->create()));
        $this->assertFalse($doctor->can('delete', Unavailability::factory()->create()));
        $this->assertRights('create', Unavailability::class, [true, false, false, false]);
    }

    /**
     * RG-16 : le patient modifie ou annule son rendez-vous actif jusqu'à 24 h avant.
     */
    public function test_a_patient_changes_their_own_appointment_until_the_deadline(): void
    {
        $patientUser = $this->users['Patient'];
        $own = Patient::factory()->for($patientUser)->create();

        $inThreeDays = Appointment::factory()->for($own)->at(now()->addDays(3))->confirmed()->create();
        $tomorrowSoon = Appointment::factory()->for($own)->at(now()->addHours(20))->confirmed()->create();
        $cancelled = Appointment::factory()->for($own)->at(now()->addDays(3))->cancelled()->create();
        $someoneElse = Appointment::factory()->at(now()->addDays(3))->confirmed()->create();

        $this->assertTrue($patientUser->can('update', $inThreeDays));
        $this->assertTrue($patientUser->can('cancel', $inThreeDays));
        $this->assertFalse($patientUser->can('cancel', $tomorrowSoon), 'moins de 24 h avant');
        $this->assertFalse($patientUser->can('update', $cancelled), 'état final');
        $this->assertFalse($patientUser->can('cancel', $someoneElse));
        $this->assertFalse($patientUser->can('confirm', $inThreeDays));

        // Passé le délai, le secrétariat peut toujours le faire.
        $this->assertTrue($this->users['Secrétaire']->can('cancel', $tomorrowSoon));
    }

    public function test_a_patient_can_see_their_own_file_and_appointments_only(): void
    {
        $patientUser = $this->users['Patient'];
        $own = Patient::factory()->for($patientUser)->create();
        $other = Patient::factory()->create();
        $ownAppointment = Appointment::factory()->create(['patient_id' => $own->id]);
        $otherAppointment = Appointment::factory()->create(['patient_id' => $other->id]);

        $this->assertTrue($patientUser->can('view', $own));
        $this->assertFalse($patientUser->can('view', $other));
        $this->assertFalse($patientUser->can('update', $own));

        $this->assertTrue($patientUser->can('view', $ownAppointment));
        $this->assertFalse($patientUser->can('view', $otherAppointment));
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $admin = $this->users['Administrateur'];

        $this->assertFalse($admin->can('updateRole', $admin));
        $this->assertTrue($admin->can('update', $admin));
    }

    public function test_patient_accounts_are_not_managed_as_staff(): void
    {
        $patientAccount = $this->users['Patient'];

        $this->assertFalse($this->users['Administrateur']->can('update', $patientAccount));
    }

    /**
     * @param  list<bool>  $expected  [médecin, secrétaire, administrateur, patient]
     */
    private function assertRights(string $ability, mixed $target, array $expected): void
    {
        foreach (array_keys($this->users) as $i => $role) {
            $this->assertSame($expected[$i], $this->users[$role]->can($ability, $target), "{$role} : {$ability}");
        }
    }
}
