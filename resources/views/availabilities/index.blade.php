{{--
    « Mes disponibilités » du médecin (maquette 12, BF-17 et BF-18).
    Variables : $availabilities, $unavailabilities, $days, $slotDurations, $settings.
--}}
<x-app-layout title="Mes disponibilités" subtitle="Les créneaux proposés aux patients = disponibilités − indisponibilités − rendez-vous déjà pris">
    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-5">
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">Semaine type</h2>
                    <p class="text-sm text-muted">
                        Horaires du cabinet : {{ $settings->time('opening_time') }} – {{ $settings->time('closing_time') }}
                        @if ($settings->isOpenOnSaturday())
                            · samedi {{ $settings->time('saturday_opening_time') }} – {{ $settings->time('saturday_closing_time') }}
                        @endif
                    </p>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jour</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Durée d'un créneau</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($availabilities as $availability)
                                <tr>
                                    <td class="font-semibold text-ink">{{ $availability->day_of_week->label() }}</td>
                                    <td class="tabular-nums">{{ $availability->startLabel() }}</td>
                                    <td class="tabular-nums">{{ $availability->endLabel() }}</td>
                                    <td>{{ $availability->slot_minutes }} min</td>
                                    <td class="text-right">
                                        @can('update', $availability)
                                            <a href="{{ route('availabilities.edit', $availability) }}" class="btn btn-secondary btn-sm"
                                               aria-label="Modifier la plage {{ $availability->label() }}">Modifier</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-muted">
                                        Aucune plage : aucun créneau n'est proposé aux patients pour le moment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @can('create', App\Models\Availability::class)
                <section class="card p-5 sm:p-6">
                    <h2 class="card-title">Ajouter une plage</h2>
                    <form method="POST" action="{{ route('availabilities.store') }}" class="mt-4 space-y-4">
                        @csrf
                        @include('availabilities.partials.fields', ['availability' => new App\Models\Availability])
                        <button type="submit" class="btn btn-primary">Ajouter la plage</button>
                    </form>
                </section>
            @endcan
        </div>

        <div class="flex flex-col gap-5">
            <section class="card overflow-hidden">
                <div class="card-header"><h2 class="card-title">Indisponibilités</h2></div>
                <ul class="divide-y divide-line-soft">
                    @forelse ($unavailabilities as $unavailability)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $unavailability->periodLabel() }}</p>
                                <p class="text-sm text-muted">{{ $unavailability->reason ?? 'Sans motif' }}</p>
                            </div>
                            @can('delete', $unavailability)
                                <form method="POST" action="{{ route('unavailabilities.destroy', $unavailability) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm"
                                            aria-label="Retirer l'indisponibilité du {{ $unavailability->periodLabel() }}">Retirer</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">Aucune indisponibilité à venir.</li>
                    @endforelse
                </ul>
            </section>

            @can('create', App\Models\Unavailability::class)
                <section class="card p-5">
                    <h2 class="card-title">Déclarer une indisponibilité</h2>
                    <form method="POST" action="{{ route('unavailabilities.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="starts_at" value="Du" required />
                            <x-text-input id="starts_at" name="starts_at" type="datetime-local" step="300" :value="old('starts_at')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('starts_at')" />
                        </div>
                        <div>
                            <x-input-label for="ends_at" value="Au" required />
                            <x-text-input id="ends_at" name="ends_at" type="datetime-local" step="300" :value="old('ends_at')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('ends_at')" />
                        </div>
                        <div>
                            <x-input-label for="reason" value="Motif" />
                            <x-text-input id="reason" name="reason" type="text" maxlength="255" placeholder="Ex. : Congé, formation" :value="old('reason')" />
                            <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                        </div>
                        <p class="text-xs text-muted">
                            Les rendez-vous déjà pris sur la période ne sont pas annulés : le secrétariat les déplace.
                        </p>
                        <button type="submit" class="btn btn-primary w-full">Déclarer</button>
                    </form>
                </section>
            @endcan
        </div>
    </div>
</x-app-layout>
