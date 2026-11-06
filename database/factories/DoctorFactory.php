<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Rôle posé directement (et non ->doctor()) pour ne pas recréer un profil.
            'user_id' => User::factory()->state(['role' => Role::Doctor]),
            'specialty_id' => Specialty::factory(),
            'presentation' => null,
        ];
    }

    /**
     * Médecin avec des attributs choisis pour son compte (nom, e-mail…).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withUser(array $attributes): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => Role::Doctor, ...$attributes]),
        ]);
    }
}
