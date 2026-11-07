<?php

namespace Database\Seeders;

use App\Enums\BloodType;
use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Patients fictifs avec leur dossier médical, dont la patiente de démonstration
 * Amira Ben Salah (maquettes 2, 6 et 13), qui a un accès en ligne.
 */
class PatientSeeder extends Seeder
{
    /** Nombre de patients générés au hasard, en plus d'Amira. */
    public const RANDOM_COUNT = 40;

    public function run(): void
    {
        $amira = Patient::factory()
            ->withUser([
                'first_name' => 'Amira', 'last_name' => 'Ben Salah',
                'email' => 'patient@cabinet.test', 'phone' => '98 451 220',
            ])
            ->create([
                'cin' => '08412337',
                'birth_date' => now()->subYears(34)->subMonths(5)->toDateString(),
                'gender' => Gender::Female,
                'address' => 'Avenue Habib Bourguiba, Midoun, Djerba',
                'created_at' => now()->subYears(2),
            ]);

        $amira->medicalRecord()->create([
            'blood_type' => BloodType::OPositive,
            'allergies' => 'Pénicilline',
            'medical_history' => "Asthme léger depuis l'enfance",
        ]);

        // Patients inscrits au fil des deux dernières années ; un sur trois
        // a été créé au cabinet sans e-mail (pas d'accès en ligne).
        for ($i = 0; $i < self::RANDOM_COUNT; $i++) {
            $factory = Patient::factory()->withMedicalRecord();

            if ($i % 3 === 0) {
                $factory = $factory->withUser(['email' => null, 'email_verified_at' => null]);
            }

            $factory->create(['created_at' => fake()->dateTimeBetween('-2 years', 'now')]);
        }
    }
}
