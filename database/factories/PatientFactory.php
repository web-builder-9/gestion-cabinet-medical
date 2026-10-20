<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Patients fictifs. Aucune donnée réelle : noms tirés au hasard dans des listes.
 *
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    private const MALE_FIRST_NAMES = [
        'Mohamed', 'Ahmed', 'Ali', 'Youssef', 'Karim', 'Sami', 'Mehdi', 'Hamza',
        'Omar', 'Bilel', 'Walid', 'Anis', 'Nizar', 'Hatem', 'Slim',
    ];

    private const FEMALE_FIRST_NAMES = [
        'Amira', 'Ines', 'Yasmine', 'Salma', 'Rania', 'Mariem', 'Sarra', 'Nour',
        'Leila', 'Hela', 'Asma', 'Olfa', 'Imen', 'Khadija', 'Fatma',
    ];

    private const LAST_NAMES = [
        'Ben Ali', 'Trabelsi', 'Jebali', 'Hammami', 'Gharbi', 'Mansouri', 'Chaabane',
        'Ayari', 'Mejri', 'Sassi', 'Khelifi', 'Dridi', 'Ferchichi', 'Bouaziz',
        'Saidi', 'Zouari', 'Haddad', 'Brahmi', 'Jlassi', 'Mabrouk',
    ];

    private const STREETS = [
        'Habib Bourguiba', 'de la République', 'Farhat Hached', 'Ibn Khaldoun',
        'Hédi Chaker', 'Mongi Slim', 'de Tunis',
    ];

    private const CITIES = ['Houmt Souk', 'Midoun', 'Ajim', 'Zarzis', 'Médenine', 'Ben Gardane', 'Gabès'];

    /**
     * Numéro tunisien fictif au format « 22 123 456 ».
     */
    public static function phoneNumber(): string
    {
        $digits = fake()->randomElement(['2', '5', '9']).fake()->numerify('#######');

        return substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 3);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $firstName = fake()->randomElement(
            $gender === Gender::Male ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES
        );
        $lastName = fake()->randomElement(self::LAST_NAMES);
        $birthDate = CarbonImmutable::instance(fake()->dateTimeBetween('-85 years', '-1 year'));

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            // La CIN n'est délivrée qu'à partir de 18 ans.
            'cin' => $birthDate->age >= 18 ? fake()->unique()->numerify('0#######') : null,
            'birth_date' => $birthDate->toDateString(),
            'gender' => $gender,
            'phone' => self::phoneNumber(),
            'email' => fake()->boolean(60)
                ? Str::slug($firstName.' '.$lastName, '.').fake()->numberBetween(1, 99).'@example.com'
                : null,
            'address' => fake()->numberBetween(1, 150).', rue '.fake()->randomElement(self::STREETS)
                .', '.fake()->randomElement(self::CITIES),
        ];
    }

    /**
     * Crée aussi le dossier médical du patient.
     * Le nom de la relation est précisé car c'est un hasOne (« medicalRecord »).
     */
    public function withMedicalRecord(): static
    {
        return $this->has(MedicalRecord::factory(), 'medicalRecord');
    }
}
