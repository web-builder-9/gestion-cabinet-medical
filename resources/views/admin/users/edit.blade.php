{{--
    Modification d'un compte du personnel (BF-24).
    Variables : $member (compte modifié, avec son profil médecin), $roles, $specialties.
--}}
<x-app-layout title="Modifier le compte" :subtitle="$member->full_name.' · '.$member->role->label()">
    <x-slot name="actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" /> Utilisateurs</a>
    </x-slot>

    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        @php($canChangeRole = Auth::user()->can('updateRole', $member))

        <form method="POST" action="{{ route('admin.users.update', $member) }}" class="card"
              x-data="{ role: @js(old('role', $member->role->value)) }">
            @csrf
            @method('PUT')

            <div class="card-header"><h2 class="card-title">Informations du compte</h2></div>

            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="last_name" value="Nom" required />
                        <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $member->last_name)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                    </div>
                    <div>
                        <x-input-label for="first_name" value="Prénom" required />
                        <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $member->first_name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Adresse e-mail" required />
                        <x-text-input id="email" name="email" type="email" :value="old('email', $member->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $member->phone)" placeholder="22 123 456" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <div class="sm:w-1/2">
                    <x-input-label for="role" value="Rôle" required />
                    @if ($canChangeRole)
                        <select id="role" name="role" x-model="role" @class(['field', 'field-error' => $errors->has('role')]) required>
                            @foreach ($roles as $option)
                                <option value="{{ $option->value }}" @selected(old('role', $member->role->value) === $option->value)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    @else
                        {{-- Personne ne modifie son propre rôle (UserPolicy::updateRole). --}}
                        <input id="role" type="text" value="{{ $member->role->label() }}" class="field bg-subtle" disabled>
                        <p class="mt-2 text-xs text-muted">Vous ne pouvez pas modifier votre propre rôle.</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>

                {{-- Profil médecin : spécialité (RG-06) et présentation affichée aux visiteurs. --}}
                <div x-show="role === 'doctor'" class="space-y-5">
                    <div class="sm:w-1/2">
                        <x-input-label for="specialty_id" value="Spécialité" required />
                        <select id="specialty_id" name="specialty_id" @class(['field', 'field-error' => $errors->has('specialty_id')]) x-bind:disabled="role !== 'doctor'">
                            <option value="">Choisir…</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" @selected((string) old('specialty_id', $member->doctor?->specialty_id) === (string) $specialty->id)>{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('specialty_id')" />
                    </div>

                    <div>
                        <x-input-label for="presentation" value="Présentation (page d'accueil)" />
                        <textarea id="presentation" name="presentation" rows="3" maxlength="500" x-bind:disabled="role !== 'doctor'"
                                  @class(['field', 'field-error' => $errors->has('presentation')])>{{ old('presentation', $member->doctor?->presentation) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('presentation')" />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-line px-5 py-4 sm:px-6">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Accès au compte</h2></div>

            <div class="space-y-5 p-5">
                <p class="flex items-center gap-2 text-sm">
                    Statut :
                    @if ($member->is_active)
                        <span class="badge bg-done text-done-ink">Actif</span>
                    @else
                        <span class="badge bg-neutral text-neutral-ink">Désactivé</span>
                    @endif
                </p>

                @if ($member->is_active)
                    <div class="space-y-2">
                        <p class="text-sm text-muted">Lien expiré ou e-mail perdu ? Envoyez un nouveau lien pour choisir le mot de passe.</p>
                        <form method="POST" action="{{ route('admin.users.invite', $member) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">Renvoyer l'invitation</button>
                        </form>
                    </div>
                @endif

                @can('toggleActive', $member)
                    <div class="space-y-2 border-t border-line pt-5">
                        @if ($member->is_active)
                            <p class="text-sm text-muted">
                                Un compte désactivé ne peut plus se connecter ; son historique est conservé.
                                @if ($member->isDoctor())
                                    Un médecin qui a des rendez-vous à venir ne peut pas être désactivé.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('admin.users.deactivate', $member) }}"
                                  onsubmit="return confirm(@js("Désactiver le compte de {$member->full_name} ?"))">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger w-full">Désactiver le compte</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $member) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-primary w-full">Réactiver le compte</button>
                            </form>
                        @endif
                    </div>
                @endcan
            </div>
        </section>
    </div>
</x-app-layout>
