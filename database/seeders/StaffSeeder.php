<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Spécialités et comptes du personnel (maquettes 1, 16 et 17).
 * Mot de passe de tous les comptes : « password ».
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = collect([
            'Médecine générale' => 'Suivi des adultes et des enfants, certificats, maladies chroniques.',
            'Pédiatrie' => 'Santé des enfants de 0 à 16 ans.',
            'Cardiologie' => 'Cœur et vaisseaux : hypertension, bilans cardiaques.',
            'Dermatologie' => 'Peau, cheveux et ongles.', // sans médecin : supprimable (RG-07)
        ])->map(fn (string $description, string $name) => Specialty::create([
            'name' => $name,
            'description' => $description,
        ]));

        User::factory()->admin()->create([
            'first_name' => 'Ridha', 'last_name' => 'Ferjani',
            'email' => 'admin@cabinet.test', 'phone' => '20 100 200',
        ]);

        User::factory()->secretary()->create([
            'first_name' => 'Nadia', 'last_name' => 'Jlassi',
            'email' => 'secretaire@cabinet.test', 'phone' => '20 100 300',
        ]);

        // Compte désactivé, pour montrer la réactivation (RG-02).
        User::factory()->secretary()->inactive()->create([
            'first_name' => 'Hela', 'last_name' => 'Mabrouk',
            'email' => 'h.mabrouk@cabinet.test', 'phone' => '20 100 400',
        ]);

        $doctors = [
            ['Salma', 'Ben Ali', 'medecin@cabinet.test', '20 200 100', 'Médecine générale',
                'Suivi des adultes et des enfants, certificats, maladies chroniques.'],
            ['Mehdi', 'Saidi', 'm.saidi@cabinet.test', '20 200 200', 'Pédiatrie',
                'Enfants de 0 à 16 ans, vaccinations, suivi de croissance.'],
            ['Leila', 'Ammar', 'l.ammar@cabinet.test', '20 200 300', 'Cardiologie',
                'Hypertension, bilans cardiaques, électrocardiogramme.'],
        ];

        foreach ($doctors as [$firstName, $lastName, $email, $phone, $specialty, $presentation]) {
            Doctor::factory()
                ->withUser(['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'phone' => $phone])
                ->create([
                    'specialty_id' => $specialties[$specialty]->id,
                    'presentation' => $presentation,
                ]);
        }
    }
}
