<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 40 patients fictifs, chacun avec son dossier médical,
 * plus un patient lié à un compte de démonstration (rôle patient).
 */
class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::factory()->count(40)->withMedicalRecord()->create();

        $account = User::factory()->patient()->create([
            'name' => 'Youssef Jebali',
            'email' => 'patient@cabinet.test',
        ]);

        Patient::factory()->withMedicalRecord()->for($account)->create([
            'first_name' => 'Youssef',
            'last_name' => 'Jebali',
            'email' => 'patient@cabinet.test',
        ]);
    }
}
