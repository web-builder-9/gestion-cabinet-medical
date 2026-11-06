<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Support\FakeIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Patients fictifs, chacun avec son compte utilisateur (rôle patient).
 *
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $birthDate = CarbonImmutable::instance(fake()->dateTimeBetween('-85 years', '-1 year'));

        return [
            'user_id' => User::factory()->patient()->state(['first_name' => FakeIdentity::firstName($gender)]),
            // La CIN n'est délivrée qu'à partir de 18 ans.
            'cin' => $birthDate->age >= 18 ? fake()->unique()->numerify('0#######') : null,
            'birth_date' => $birthDate->toDateString(),
            'gender' => $gender,
            'address' => FakeIdentity::address(),
            'data_consent' => true,
            'consent_given_at' => now(),
        ];
    }

    /**
     * Patient avec des attributs choisis pour son compte (nom, prénom, téléphone, e-mail…).
     * Exemple : Patient::factory()->withUser(['first_name' => 'Amira'])->create();
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withUser(array $attributes): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->patient()->state($attributes),
        ]);
    }

    /**
     * Crée aussi le dossier médical (relation hasOne « medicalRecord »).
     */
    public function withMedicalRecord(): static
    {
        return $this->has(MedicalRecord::factory(), 'medicalRecord');
    }
}
