<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Paramètres du cabinet (BF-26) : horaires, durée d'un créneau, délais.
 */
class SettingRequest extends FormRequest
{
    /** Choix proposés dans les listes de la page (maquette 17). */
    public const SLOT_DURATIONS = [10, 15, 20, 30, 45, 60]; // minutes (RG-09)

    public const CANCELLATION_HOURS = [2, 6, 12, 24, 48]; // annulation par le patient (RG-16)

    public const BOOKING_DAYS = [7, 14, 30, 60, 90]; // réservation au plus tard (RG-14)

    public const REMINDER_HOURS = [2, 12, 24, 48]; // rappel par e-mail (BF-29)

    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::current());
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::format($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cabinet_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:'.PhoneNumber::PATTERN],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            // Samedi : les deux heures, ou aucune (cabinet fermé).
            'saturday_opening_time' => ['nullable', 'required_with:saturday_closing_time', 'date_format:H:i'],
            'saturday_closing_time' => ['nullable', 'required_with:saturday_opening_time', 'date_format:H:i', 'after:saturday_opening_time'],
            'default_slot_minutes' => ['required', 'integer', Rule::in(self::SLOT_DURATIONS)],
            'cancellation_hours' => ['required', 'integer', Rule::in(self::CANCELLATION_HOURS)],
            'booking_days_ahead' => ['required', 'integer', Rule::in(self::BOOKING_DAYS)],
            'reminder_hours' => ['required', 'integer', Rule::in(self::REMINDER_HOURS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'closing_time.after' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture.',
            'saturday_closing_time.after' => 'Le samedi, la fermeture doit être après l\'ouverture.',
            'phone.regex' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 75 600 600).',
        ];
    }
}
