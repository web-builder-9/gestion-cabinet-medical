<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Comptes rendus fictifs.
 *
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    public const CASES = [
        ['Toux persistante', 'Toux sèche, pas de fièvre. Quelques sibilants.', 'Bronchite aiguë', 'Repos, boire beaucoup ; revoir si la toux persiste au-delà de 10 jours.'],
        ['Fièvre', 'Température 38,5 °C, gorge rouge.', 'Angine virale', 'Paracétamol si fièvre ; repos 3 jours.'],
        ['Suivi tension artérielle', 'TA 14/9 au repos.', 'Hypertension modérée', 'Réduire le sel ; contrôle dans un mois.'],
        ['Douleurs abdominales', 'Abdomen souple, douleur épigastrique.', 'Gastrite', 'Éviter épices et café ; repas légers.'],
        ['Contrôle', 'Examen clinique normal.', 'Bon état général', 'Aucun traitement ; contrôle annuel.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$reason, $observations, $diagnosis, $treatment] = fake()->randomElement(self::CASES);

        return [
            'appointment_id' => Appointment::factory()->completed(),
            'consulted_at' => fn (array $attributes) => Appointment::find($attributes['appointment_id'])->starts_at,
            'reason' => $reason,
            'observations' => $observations,
            'diagnosis' => $diagnosis,
            'treatment' => $treatment,
        ];
    }
}
