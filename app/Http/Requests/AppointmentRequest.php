<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Prise (BF-13) ou déplacement (BF-14) d'un rendez-vous par le personnel.
 * Les règles du créneau (RG-11 à RG-14) sont dans le service Booking.
 */
class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->appointment();

        return $appointment
            ? $this->user()->can('update', $appointment)
            : $this->user()->can('create', Appointment::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Lors d'un déplacement, le patient ne change pas.
            'patient_id' => $this->appointment()
                ? ['prohibited']
                : ['required', 'integer', Rule::exists(Patient::class, 'id')->withoutTrashed()],
            'doctor_id' => ['required', 'integer', Rule::exists(Doctor::class, 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Vérifie le créneau seulement si les champs eux-mêmes sont valides.
     *
     * @return list<callable>
     */
    public function after(Booking $booking): array
    {
        return [
            function (Validator $validator) use ($booking) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! $this->doctor()->user->is_active) {
                    $validator->errors()->add('doctor_id', 'Ce médecin n\'est plus en activité.');

                    return;
                }

                $problem = $booking->problem($this->doctor(), $this->patient(), $this->start(), $this->appointment());

                if ($problem) {
                    $validator->errors()->add('time', $problem);
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
            'patient_id.required' => 'Choisissez le patient.',
            'time.required' => 'Choisissez un créneau.',
        ];
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('time'))->startOfMinute();
    }

    public function doctor(): Doctor
    {
        return Doctor::with('user')->findOrFail($this->integer('doctor_id'));
    }

    public function patient(): Patient
    {
        return $this->appointment()?->patient ?? Patient::with('user')->findOrFail($this->integer('patient_id'));
    }

    /**
     * Le rendez-vous déplacé, ou null lors d'une prise de rendez-vous.
     */
    public function appointment(): ?Appointment
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment ? $appointment : null;
    }
}
