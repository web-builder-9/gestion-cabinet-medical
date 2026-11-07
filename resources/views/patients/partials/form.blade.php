{{--
    Formulaire commun à l'ajout et à la modification d'un dossier patient (maquette 9).
    Variables : $patient (Patient avec son user), $genders (liste de App\Enums\Gender).
--}}
<section class="space-y-5 p-5 sm:p-6">
    <h2 class="card-title">Identité</h2>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <x-input-label for="last_name" value="Nom" required />
            <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $patient->user->last_name)" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <div>
            <x-input-label for="first_name" value="Prénom" required />
            <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $patient->user->first_name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <div>
            <x-input-label for="birth_date" value="Date de naissance" required />
            <x-text-input id="birth_date" name="birth_date" type="date" :max="now()->format('Y-m-d')" required
                          :value="old('birth_date', $patient->birth_date?->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
        </div>
    </div>

    <fieldset>
        <legend class="label">Genre <span class="text-danger-ink" aria-hidden="true">*</span></legend>
        <div class="mt-2 flex gap-3">
            @foreach ($genders as $gender)
                <label class="flex h-11 cursor-pointer items-center gap-2 rounded-lg border border-line-strong px-4 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                    <input type="radio" name="gender" value="{{ $gender->value }}" class="text-brand focus:ring-brand"
                           @checked(old('gender', $patient->gender?->value) === $gender->value)>
                    {{ $gender === App\Enums\Gender::Female ? 'Femme' : 'Homme' }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('gender')" />
    </fieldset>

    <div class="sm:w-1/3">
        <x-input-label for="cin" value="CIN" />
        <x-text-input id="cin" name="cin" type="text" inputmode="numeric" maxlength="8" placeholder="8 chiffres"
                      :value="old('cin', $patient->cin)" />
        <x-input-error class="mt-2" :messages="$errors->get('cin')" />
    </div>
</section>

<section class="space-y-5 border-t border-line p-5 sm:p-6">
    <h2 class="card-title">Coordonnées</h2>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" value="Téléphone" required />
            <x-text-input id="phone" name="phone" type="tel" placeholder="22 123 456" required
                          :value="old('phone', $patient->user->phone)" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $patient->user->email)" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>
    </div>

    <div>
        <x-input-label for="address" value="Adresse" />
        <x-text-input id="address" name="address" type="text" :value="old('address', $patient->address)" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>
</section>

@unless ($patient->exists)
    <section class="space-y-3 border-t border-line bg-subtle p-5 sm:p-6">
        <h2 class="card-title">Consentement</h2>
        <label class="flex items-start gap-3 text-sm text-ink-2">
            <input type="checkbox" name="data_consent" value="1" @checked(old('data_consent'))
                   class="mt-0.5 rounded border-line-strong text-brand focus:ring-brand">
            <span>
                Le patient a donné son accord pour le traitement de ses données personnelles et de santé
                (loi organique n° 2004-63).
            </span>
        </label>
        <x-input-error :messages="$errors->get('data_consent')" />
    </section>
@endunless
