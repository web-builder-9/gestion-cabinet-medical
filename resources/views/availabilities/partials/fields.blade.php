{{--
    Champs d'une plage de la semaine type (ajout et modification).
    Variables : $availability (Availability, nouvelle ou existante), $days, $slotDurations, $settings.
--}}
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <x-input-label for="day_of_week" value="Jour" required />
        <select id="day_of_week" name="day_of_week" @class(['field', 'field-error' => $errors->has('day_of_week')]) required>
            @foreach ($days as $day)
                <option value="{{ $day->value }}" @selected((int) old('day_of_week', $availability->day_of_week?->value) === $day->value)>{{ $day->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('day_of_week')" />
    </div>

    <div>
        <x-input-label for="start_time" value="Début" required />
        <x-text-input id="start_time" name="start_time" type="time" step="300" required
                      :value="old('start_time', $availability->exists ? $availability->startLabel() : null)" />
    </div>

    <div>
        <x-input-label for="end_time" value="Fin" required />
        <x-text-input id="end_time" name="end_time" type="time" step="300" required
                      :class="$errors->has('start_time') ? 'field-error' : ''"
                      :value="old('end_time', $availability->exists ? $availability->endLabel() : null)" />
    </div>
</div>

{{-- Une erreur sur la plage (horaires, chevauchement) concerne le début et la fin. --}}
<x-input-error :messages="[...$errors->get('start_time'), ...$errors->get('end_time')]" />

<div class="sm:w-1/3">
    <x-input-label for="slot_minutes" value="Créneau" required />
    <select id="slot_minutes" name="slot_minutes" @class(['field', 'field-error' => $errors->has('slot_minutes')]) required>
        @foreach ($slotDurations as $minutes)
            <option value="{{ $minutes }}" @selected((int) old('slot_minutes', $availability->slot_minutes ?? $settings->default_slot_minutes) === $minutes)>{{ $minutes }} min</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('slot_minutes')" />
</div>
