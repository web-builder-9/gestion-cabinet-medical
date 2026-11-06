{{-- Inscription d'un patient (maquette 2, BF-03). Variables : $genders. --}}
<x-guest-layout title="Créer mon compte patient" width="xl"
                subtitle="Gratuit. Il vous permet de réserver, de suivre vos rendez-vous et de lire vos comptes rendus.">
    @if ($errors->any())
        <div class="alert alert-danger mb-6" role="alert">
            <x-icon name="warning" />
            <span>
                <strong class="font-semibold">Le compte n'a pas été créé.</strong>
                {{ trans_choice('Corrigez le champ signalé.|Corrigez les :count champs signalés.', count($errors->keys())) }}
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="last_name" value="Nom" required />
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name')" required autofocus autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
            <div>
                <x-input-label for="first_name" value="Prénom" required />
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name')" required autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="email" value="Adresse e-mail" required />
                <x-text-input id="email" name="email" type="email" :value="old('email')" required autocomplete="email" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label for="phone" value="Téléphone" required />
                <x-text-input id="phone" name="phone" type="tel" placeholder="22 123 456" :value="old('phone')" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="birth_date" value="Date de naissance" required />
                <x-text-input id="birth_date" name="birth_date" type="date" :max="now()->format('Y-m-d')" :value="old('birth_date')" required autocomplete="bday" />
                <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
            </div>
            <fieldset>
                <legend class="label">Genre <span class="text-danger-ink" aria-hidden="true">*</span></legend>
                <div class="mt-1 flex gap-3">
                    @foreach ($genders as $gender)
                        <label class="flex h-[42px] cursor-pointer items-center gap-2 rounded-lg border border-line-strong px-4 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                            <input type="radio" name="gender" value="{{ $gender->value }}" class="text-brand focus:ring-brand" @checked(old('gender') === $gender->value)>
                            {{ $gender === App\Enums\Gender::Female ? 'Femme' : 'Homme' }}
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
            </fieldset>
            <div>
                <x-input-label for="password" value="Mot de passe" required />
                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                <x-input-error class="mt-2" :messages="array_unique($errors->get('password'))" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirmer le mot de passe" required />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </div>
        </div>

        <div class="rounded-lg bg-subtle p-4">
            <label class="flex items-start gap-3 text-sm text-ink-2">
                <input type="checkbox" name="data_consent" value="1" @checked(old('data_consent'))
                       class="mt-0.5 rounded border-line-strong text-brand focus:ring-brand">
                <span>
                    J'accepte que le cabinet traite mes données personnelles et de santé pour gérer mes rendez-vous et
                    mon suivi médical (loi organique n° 2004-63). Je peux consulter et corriger mes données à tout moment.
                </span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('data_consent')" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('login') }}" class="link text-sm">J'ai déjà un compte</a>
            <button type="submit" class="btn btn-primary">Créer mon compte</button>
        </div>
    </form>
</x-guest-layout>
