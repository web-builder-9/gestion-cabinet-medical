{{--
    Formulaire commun à l'ajout et à la modification d'un patient.
    Variables : $patient (Patient), $genders (liste de App\Enums\Gender).
--}}
<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="last_name" value="Nom" />
        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full"
                      :value="old('last_name', $patient->last_name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
    </div>

    <div>
        <x-input-label for="first_name" value="Prénom" />
        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full"
                      :value="old('first_name', $patient->first_name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
    </div>

    <div>
        <x-input-label for="birth_date" value="Date de naissance" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full"
                      :value="old('birth_date', $patient->birth_date?->format('Y-m-d'))"
                      :max="now()->format('Y-m-d')" required />
        <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
    </div>

    <div>
        <x-input-label for="gender" value="Sexe" />
        <select id="gender" name="gender" required
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
            <option value="">—</option>
            @foreach ($genders as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $patient->gender?->value) === $gender->value)>
                    {{ $gender->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('gender')" />
    </div>

    <div>
        <x-input-label for="cin" value="CIN (facultatif)" />
        <x-text-input id="cin" name="cin" type="text" inputmode="numeric" maxlength="8" class="mt-1 block w-full"
                      :value="old('cin', $patient->cin)" placeholder="8 chiffres" />
        <x-input-error class="mt-2" :messages="$errors->get('cin')" />
    </div>

    <div>
        <x-input-label for="phone" value="Téléphone" />
        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                      :value="old('phone', $patient->phone)" placeholder="22 123 456" required />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
    </div>

    <div>
        <x-input-label for="email" value="Adresse e-mail (facultatif)" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $patient->email)" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="address" value="Adresse (facultatif)" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                      :value="old('address', $patient->address)" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>
</div>
