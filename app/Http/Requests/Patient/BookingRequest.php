<?php

namespace App\Http\Requests\Patient;

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
 * Demande de rendez-vous (BF-08) ou modification (BF-09) par le patient connecté.
 * Mêmes règles de créneau que pour le secrétariat (service Booking).
 */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->appointment();

        return $appointment
            ? $this->user()->can('update', $appointment)
            : $this->user()->isPatient() && $this->user()->patient !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // En modification, le médecin ne change pas.
            'doctor_id' => $this->appointment() ? ['prohibited'] : ['required', 'integer', Rule::exists(Doctor::class, 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
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
                    $validator->errors()->add('time', 'Ce médecin ne prend plus de rendez-vous.');

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
        return ['time.required' => 'Choisissez un créneau.'];
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('time'))->startOfMinute();
    }

    public function doctor(): Doctor
    {
        return $this->appointment()?->doctor ?? Doctor::with('user')->findOrFail($this->integer('doctor_id'));
    }

    public function patient(): Patient
    {
        return $this->user()->patient;
    }

    public function appointment(): ?Appointment
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment ? $appointment : null;
    }
}
