<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration 100 % fictives, cohérentes avec les maquettes.
     * Commande : php artisan migrate:fresh --seed
     *
     * Comptes (mot de passe « password ») :
     *   admin@cabinet.test (administrateur), secretaire@cabinet.test (secrétaire),
     *   medecin@cabinet.test (médecin), patient@cabinet.test (patiente).
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            StaffSeeder::class,
            AvailabilitySeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}
