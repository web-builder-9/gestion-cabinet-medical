{{--
    Formulaire commun à l'ajout et à la modification d'un membre du personnel.
    Variables : $member (User), $roles (liste de App\Enums\Role).
--}}
@php
    $editing = $member->exists;
    $canChangeRole = $editing ? Auth::user()->can('updateRole', $member) : true;
@endphp

<div>
    <x-input-label for="name" value="Nom complet" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $member->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="email" value="Adresse e-mail" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                  :value="old('email', $member->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div>
    <x-input-label for="phone" value="Téléphone (facultatif)" />
    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                  :value="old('phone', $member->phone)" placeholder="22 123 456" />
    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
</div>

<div>
    <x-input-label for="role" value="Rôle" />
    @if ($canChangeRole)
        <select id="role" name="role" required
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $member->role?->value) === $role->value)>
                    {{ $role->label() }}
                </option>
            @endforeach
        </select>
    @else
        {{-- Son propre rôle ne se modifie pas (voir UserPolicy::updateRole). --}}
        <p class="mt-1 text-sm text-gray-700">
            {{ $member->role->label() }}
            <span class="text-gray-500">— vous ne pouvez pas modifier votre propre rôle.</span>
        </p>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get('role')" />
</div>

<div>
    <x-input-label for="password" :value="$editing ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                  autocomplete="new-password" :required="! $editing" />
    <x-input-error class="mt-2" :messages="$errors->get('password')" />
</div>

<div>
    <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                  class="mt-1 block w-full" autocomplete="new-password" :required="! $editing" />
</div>
