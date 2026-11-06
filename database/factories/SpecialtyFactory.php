<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    private const NAMES = [
        'Médecine générale', 'Pédiatrie', 'Cardiologie', 'Dermatologie', 'Gynécologie',
        'Ophtalmologie', 'ORL', 'Rhumatologie', 'Pneumologie', 'Endocrinologie',
        'Gastro-entérologie', 'Neurologie', 'Psychiatrie', 'Urologie', 'Néphrologie',
        'Orthopédie', 'Allergologie', 'Gériatrie', 'Médecine du sport', 'Nutrition',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(self::NAMES),
            'description' => null,
        ];
    }
}
