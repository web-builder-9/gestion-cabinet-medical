<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matrice des droits : reprend le tableau « Rôles et droits d'accès » de CLAUDE.md.
 * Chaque ligne : [action, médecin, secrétaire, patient] ; le patient testé
 * n'est pas celui de la fiche (ce cas est testé à part).
 */
class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private User $secretary;

    private User $patientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->doctor()->create();
        $this->secretary = User::factory()->secretary()->create();
        $this->patientUser = User::factory()->patient()->create();
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function patientMatrix(): array
    {
        return [
            'voir la liste' => ['viewAny', true, true, false],
            'voir une fiche' => ['view', true, true, false],
            'créer une fiche' => ['create', true, true, false],
            'modifier une fiche' => ['update', true, true, false],
            'supprimer une fiche' => ['delete', true, false, false],
            'restaurer une fiche' => ['restore', true, false, false],
            'supprimer pour de bon' => ['forceDelete', false, false, false],
        ];
    }

    #[DataProvider('patientMatrix')]
    public function test_patient_rights(string $ability, bool $doctor, bool $secretary, bool $patient): void
    {
        $record = Patient::factory()->create();
        $target = in_array($ability, ['viewAny', 'create'], true) ? Patient::class : $record;

        $this->assertRights($ability, $target, $doctor, $secretary, $patient);
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function medicalRecordMatrix(): array
    {
        return [
            'voir un dossier' => ['view', true, false, false],
            'créer un dossier' => ['create', true, false, false],
            'modifier un dossier' => ['update', true, false, false],
        ];
    }

    #[DataProvider('medicalRecordMatrix')]
    public function test_medical_record_rights(string $ability, bool $doctor, bool $secretary, bool $patient): void
    {
        $record = MedicalRecord::factory()->create();
        $target = $ability === 'create' ? MedicalRecord::class : $record;

        $this->assertRights($ability, $target, $doctor, $secretary, $patient);
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function appointmentMatrix(): array
    {
        return [
            'voir la liste' => ['viewAny', true, true, false],
            'voir un RDV' => ['view', true, true, false],
            'créer un RDV' => ['create', true, true, false],
            'modifier un RDV' => ['update', true, true, false],
            'supprimer un RDV' => ['delete', true, true, false],
        ];
    }

    #[DataProvider('appointmentMatrix')]
    public function test_appointment_rights(string $ability, bool $doctor, bool $secretary, bool $patient): void
    {
        $appointment = Appointment::factory()->create();
        $target = in_array($ability, ['viewAny', 'create'], true) ? Appointment::class : $appointment;

        $this->assertRights($ability, $target, $doctor, $secretary, $patient);
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function staffMatrix(): array
    {
        return [
            'voir le personnel' => ['viewAny', true, false, false],
            'créer un compte' => ['create', true, false, false],
            'modifier un compte' => ['update', true, false, false],
            'changer un rôle' => ['updateRole', true, false, false],
            'supprimer un compte' => ['delete', false, false, false],
        ];
    }

    #[DataProvider('staffMatrix')]
    public function test_staff_management_rights(string $ability, bool $doctor, bool $secretary, bool $patient): void
    {
        $account = User::factory()->secretary()->create();
        $target = in_array($ability, ['viewAny', 'create'], true) ? User::class : $account;

        $this->assertRights($ability, $target, $doctor, $secretary, $patient);
    }

    public function test_a_patient_can_see_their_own_record_and_appointments_only(): void
    {
        $own = Patient::factory()->for($this->patientUser)->create();
        $other = Patient::factory()->create();
        $ownAppointment = Appointment::factory()->create(['patient_id' => $own->id]);
        $otherAppointment = Appointment::factory()->create(['patient_id' => $other->id]);

        $this->assertTrue($this->patientUser->can('view', $own));
        $this->assertFalse($this->patientUser->can('view', $other));
        $this->assertFalse($this->patientUser->can('update', $own));

        $this->assertTrue($this->patientUser->can('view', $ownAppointment));
        $this->assertFalse($this->patientUser->can('view', $otherAppointment));
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $this->assertFalse($this->doctor->can('updateRole', $this->doctor));
        $this->assertTrue($this->doctor->can('update', $this->doctor));
    }

    public function test_patient_accounts_are_not_managed_as_staff(): void
    {
        $this->assertFalse($this->doctor->can('update', $this->patientUser));
        $this->assertFalse($this->doctor->can('updateRole', $this->patientUser));
    }

    private function assertRights(string $ability, mixed $target, bool $doctor, bool $secretary, bool $patient): void
    {
        $this->assertSame($doctor, $this->doctor->can($ability, $target), "Médecin : {$ability}");
        $this->assertSame($secretary, $this->secretary->can($ability, $target), "Secrétaire : {$ability}");
        $this->assertSame($patient, $this->patientUser->can($ability, $target), "Patient : {$ability}");
    }
}
