{{--
    Comptes du personnel (maquette 16, BF-23 et BF-24).
    Variables : $members (comptes du personnel), $role (onglet choisi ou null),
    $roles (rôles du personnel), $specialties.
--}}
<x-app-layout title="Utilisateurs" subtitle="Comptes du personnel : un seul rôle par compte">
    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <section class="card overflow-hidden">
            <div class="border-b border-line p-4">
                <nav class="segmented" aria-label="Filtrer par rôle">
                    <a href="{{ route('admin.users.index') }}" @if (! $role) aria-current="page" @endif>Tous</a>
                    @foreach ($roles as $option)
                        <a href="{{ route('admin.users.index', ['role' => $option->value]) }}"
                           @if ($role === $option) aria-current="page" @endif>{{ $option->pluralLabel() }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Rôle et spécialité</th>
                            <th>Statut</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td>
                                    <div class="font-semibold text-ink">{{ $member->isDoctor() && $member->doctor ? $member->doctor->display_name : $member->full_name }}</div>
                                    <div class="text-xs break-all text-muted">{{ $member->email }}</div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <x-role-badge :role="$member->role" />
                                    @if ($member->isDoctor() && $member->doctor)
                                        <span class="ml-1 text-[13px] text-muted">{{ $member->doctor->specialty->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($member->is_active)
                                        <span class="badge bg-done text-done-ink">Actif</span>
                                    @else
                                        <span class="badge bg-neutral text-neutral-ink">Désactivé</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($member->is(Auth::user()))
                                        <span class="text-sm text-muted">Vous</span>
                                    @elseif (! $member->is_active)
                                        @can('toggleActive', $member)
                                            <form method="POST" action="{{ route('admin.users.activate', $member) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-secondary btn-sm">Réactiver</button>
                                            </form>
                                        @endcan
                                    @else
                                        @can('update', $member)
                                            <a href="{{ route('admin.users.edit', $member) }}" class="btn btn-secondary btn-sm">Modifier</a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-muted">Aucun compte dans cette catégorie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @can('create', App\Models\User::class)
            <section class="card" x-data="{ role: @js(old('role', App\Enums\Role::Doctor->value)) }">
                <div class="card-header"><h2 class="card-title">Créer un compte</h2></div>

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 p-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        <div>
                            <x-input-label for="last_name" value="Nom" required />
                            <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name')" placeholder="Ex. : Bouzid" required />
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>
                        <div>
                            <x-input-label for="first_name" value="Prénom" required />
                            <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name')" placeholder="Ex. : Karim" required />
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" value="Adresse e-mail" required />
                        <x-text-input id="email" name="email" type="email" :value="old('email')" placeholder="nom@cabinet.test" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input id="phone" name="phone" type="tel" :value="old('phone')" placeholder="22 123 456" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="role" value="Rôle" required />
                        <select id="role" name="role" x-model="role" @class(['field', 'field-error' => $errors->has('role')]) required>
                            @foreach ($roles as $option)
                                <option value="{{ $option->value }}" @selected(old('role', App\Enums\Role::Doctor->value) === $option->value)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    {{-- RG-06 : la spécialité ne concerne que les médecins. --}}
                    <div x-show="role === 'doctor'">
                        <x-input-label for="specialty_id" value="Spécialité (médecin)" />
                        <select id="specialty_id" name="specialty_id" @class(['field', 'field-error' => $errors->has('specialty_id')]) x-bind:disabled="role !== 'doctor'">
                            <option value="">Choisir…</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" @selected((string) old('specialty_id') === (string) $specialty->id)>{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('specialty_id')" />
                    </div>

                    <p class="text-sm text-muted">
                        Un e-mail invite la personne à choisir son mot de passe. Les comptes patients sont créés
                        par le patient ou par le secrétariat.
                    </p>

                    <button type="submit" class="btn btn-primary w-full">Créer le compte</button>
                </form>
            </section>
        @endcan
    </div>
</x-app-layout>
