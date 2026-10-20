<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dossiers médicaux fictifs.
 *
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    private const ALLERGIES = [
        'Pénicilline', 'Aspirine', 'Arachides', 'Pollen', 'Acariens', 'Lactose', 'Fruits de mer',
    ];

    private const HISTORY = [
        'Hypertension artérielle', 'Diabète de type 2', 'Asthme', 'Appendicectomie',
        'Hypothyroïdie', 'Migraine chronique', 'Fracture du poignet (ancienne)',
    ];

    private const TREATMENTS = [
        'Metformine 850 mg, 2 fois par jour',
        'Amlodipine 5 mg, 1 fois par jour',
        'Lévothyroxine 50 µg, le matin',
        'Salbutamol inhalé en cas de crise',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'blood_type' => fake()->randomElement(BloodType::cases()),
            'allergies' => fake()->boolean(30)
                ? implode(', ', fake()->randomElements(self::ALLERGIES, fake()->numberBetween(1, 2)))
                : null,
            'medical_history' => fake()->boolean(50)
                ? implode(', ', fake()->randomElements(self::HISTORY, fake()->numberBetween(1, 2)))
                : null,
            'current_treatments' => fake()->boolean(35) ? fake()->randomElement(self::TREATMENTS) : null,
        ];
    }
}
