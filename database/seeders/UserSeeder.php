<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de démonstration du personnel (mot de passe : « password »).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->doctor()->create([
            'name' => 'Dr Sami Ben Salah',
            'email' => 'medecin@cabinet.test',
        ]);

        User::factory()->secretary()->create([
            'name' => 'Amira Trabelsi',
            'email' => 'secretaire@cabinet.test',
        ]);
    }
}
