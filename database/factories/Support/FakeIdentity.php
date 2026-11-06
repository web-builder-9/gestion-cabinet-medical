<?php

namespace Database\Factories\Support;

use App\Enums\Gender;

/**
 * Identités fictives tunisiennes pour les factories et les seeders.
 * Aucune donnée réelle : noms et numéros tirés au hasard.
 */
class FakeIdentity
{
    public const MALE_FIRST_NAMES = [
        'Mohamed', 'Ahmed', 'Ali', 'Youssef', 'Karim', 'Sami', 'Mehdi', 'Hamza',
        'Omar', 'Bilel', 'Walid', 'Anis', 'Nizar', 'Hatem', 'Slim', 'Hedi',
    ];

    public const FEMALE_FIRST_NAMES = [
        'Amira', 'Ines', 'Yasmine', 'Salma', 'Rania', 'Mariem', 'Sarra', 'Nour',
        'Leila', 'Hela', 'Asma', 'Olfa', 'Imen', 'Khadija', 'Fatma', 'Sonia',
    ];

    public const LAST_NAMES = [
        'Ben Ali', 'Trabelsi', 'Jebali', 'Hammami', 'Gharbi', 'Mansouri', 'Chaabane',
        'Ayari', 'Mejri', 'Sassi', 'Khelifi', 'Dridi', 'Ferchichi', 'Bouaziz',
        'Saidi', 'Zouari', 'Haddad', 'Brahmi', 'Khemiri', 'Mrad', 'Toumi', 'Hamdi',
    ];

    private const STREETS = [
        'Habib Bourguiba', 'de la République', 'Farhat Hached', 'Ibn Khaldoun',
        'Hédi Chaker', 'Mongi Slim', 'de Tunis',
    ];

    private const CITIES = ['Houmt Souk', 'Midoun', 'Ajim', 'Zarzis', 'Médenine', 'Ben Gardane'];

    public static function firstName(?Gender $gender = null): string
    {
        $gender ??= fake()->randomElement(Gender::cases());

        return fake()->randomElement(
            $gender === Gender::Male ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES
        );
    }

    public static function lastName(): string
    {
        return fake()->randomElement(self::LAST_NAMES);
    }

    /**
     * Numéro fictif unique au format « 22 123 456 ».
     */
    public static function phone(): string
    {
        $digits = fake()->unique()->numerify(fake()->randomElement(['2', '5', '9']).'#######');

        return substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 3);
    }

    public static function address(): string
    {
        return fake()->numberBetween(1, 150).', rue '.fake()->randomElement(self::STREETS)
            .', '.fake()->randomElement(self::CITIES);
    }
}
