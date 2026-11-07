<?php

namespace App\Http\Requests\Doctor;

use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Compte rendu d'une consultation et traitement prescrit (BF-21, maquette 14).
 */
class ConsultationRequest extends FormRequest
{
    /** Nombre maximal de médicaments sur une ordonnance. */
    public const MAX_ITEMS = 15;

    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation instanceof Consultation
            ? $this->user()->can('update', $consultation)
            : $this->user()->can('create', [Consultation::class, $this->route('appointment')]);
    }

    /**
     * Les lignes entièrement vides du tableau des médicaments sont ignorées.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && collect($item)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'treatment' => ['nullable', 'string', 'max:2000'],
            'items' => ['array', 'max:'.self::MAX_ITEMS],
            // Une ligne commencée indique au moins le médicament et sa posologie.
            'items.*.medication' => ['required', 'string', 'max:150'],
            'items.*.dosage' => ['nullable', 'string', 'max:50'],
            'items.*.posology' => ['required', 'string', 'max:255'],
            'items.*.duration' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.medication.required' => 'Indiquez le médicament de la ligne :position.',
            'items.*.posology.required' => 'Indiquez la posologie de la ligne :position.',
            'items.max' => 'Une ordonnance contient au plus :max médicaments.',
        ];
    }

    /**
     * Données du compte rendu (sans les médicaments).
     *
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return $this->safe()->only(['reason', 'observations', 'diagnosis', 'treatment']);
    }

    /**
     * Lignes de l'ordonnance, numérotées dans l'ordre de saisie.
     *
     * @return list<array<string, mixed>>
     */
    public function prescriptionItems(): array
    {
        return collect($this->validated('items', []))
            ->values()
            ->map(fn (array $item, int $index) => [...$item, 'position' => $index + 1])
            ->all();
    }

    /**
     * Bouton « Enregistrer et imprimer l'ordonnance ».
     */
    public function wantsPrescription(): bool
    {
        return $this->input('next') === 'prescription';
    }

    public function appointment(): ?Appointment
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment ? $appointment : null;
    }
}
