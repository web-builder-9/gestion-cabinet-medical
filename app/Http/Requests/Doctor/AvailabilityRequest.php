<?php

namespace App\Http\Requests\Doctor;

use App\Enums\DayOfWeek;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Availability;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Ajout ou modification d'une plage de la semaine type (BF-17).
 * RG-09 : jour, heure de début, heure de fin, durée d'un créneau.
 * RG-10 : dans les horaires du cabinet, sans chevaucher une autre plage du médecin.
 */
class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $availability = $this->availability();

        return $availability
            ? $this->user()->can('update', $availability)
            : $this->user()->can('create', Availability::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', Rule::enum(DayOfWeek::class)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['required', 'integer', Rule::in(SettingRequest::SLOT_DURATIONS)],
        ];
    }

    /**
     * Règles qui comparent avec les paramètres du cabinet et les autres plages,
     * vérifiées seulement si les champs eux-mêmes sont valides.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($message = $this->problem()) {
                    $validator->errors()->add('start_time', $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ];
    }

    /**
     * La plage modifiée, ou null lors d'un ajout.
     */
    public function availability(): ?Availability
    {
        $availability = $this->route('availability');

        return $availability instanceof Availability ? $availability : null;
    }

    /**
     * Premier problème trouvé (RG-10), ou null si la plage est acceptable.
     */
    private function problem(): ?string
    {
        $day = $this->enum('day_of_week', DayOfWeek::class);
        $start = $this->input('start_time');
        $end = $this->input('end_time');
        $settings = Setting::current();

        // Horaires du cabinet ce jour-là.
        if ($day === DayOfWeek::Saturday) {
            if (! $settings->isOpenOnSaturday()) {
                return 'Le cabinet est fermé le samedi.';
            }
            [$opening, $closing] = [$settings->time('saturday_opening_time'), $settings->time('saturday_closing_time')];
        } else {
            [$opening, $closing] = [$settings->time('opening_time'), $settings->time('closing_time')];
        }

        if ($start < $opening || $end > $closing) {
            return "La plage doit rester dans les horaires du cabinet ({$day->label()} : {$opening} – {$closing}).";
        }

        // Au moins un créneau complet dans la plage.
        $minutes = (strtotime($end) - strtotime($start)) / 60;
        if ($minutes < $this->integer('slot_minutes')) {
            return "La plage doit contenir au moins un créneau de {$this->integer('slot_minutes')} min.";
        }

        // Pas de chevauchement avec une autre plage du même médecin, le même jour.
        $overlapping = $this->user()->doctor->availabilities()
            ->where('day_of_week', $day)
            ->when($this->availability(), fn ($q, $availability) => $q->whereKeyNot($availability->id))
            ->get()
            ->first(fn (Availability $other) => $other->overlaps($start, $end));

        if ($overlapping) {
            return "Cette plage chevauche « {$overlapping->label()} ». Deux plages d'un même médecin ne peuvent pas se chevaucher.";
        }

        return null;
    }
}
