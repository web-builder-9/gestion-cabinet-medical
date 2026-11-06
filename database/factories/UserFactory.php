<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Doctor;
use App\Models\User;
use Database\Factories\Support\FakeIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_name' => FakeIdentity::lastName(),
            'first_name' => FakeIdentity::firstName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => FakeIdentity::phone(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Patient,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function patient(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Patient]);
    }

    public function secretary(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Secretary]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Admin]);
    }

    /**
     * Compte médecin, avec son profil (table doctors) et une spécialité.
     */
    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Doctor])
            ->afterCreating(function (User $user) {
                if (! $user->doctor()->exists()) {
                    Doctor::factory()->for($user)->create();
                }
            });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Patient créé au cabinet sans adresse e-mail (pas d'accès en ligne).
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes) => ['email' => null, 'email_verified_at' => null]);
    }
}
