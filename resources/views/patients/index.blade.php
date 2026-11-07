<x-app-layout title="Patients" :subtitle="trans_choice(':count patient enregistré|:count patients enregistrés', $total)">
    <x-slot name="actions">
        @can('viewArchived', App\Models\Patient::class)
            <a href="{{ route('patients.archived') }}" class="btn btn-secondary">
                <x-icon name="archive" /> Archives
            </a>
        @endcan
        @can('create', App\Models\Patient::class)
            <a href="{{ route('patients.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Nouveau patient
            </a>
        @endcan
    </x-slot>

    <x-flash-status />

    <div class="card">
        <form method="GET" action="{{ route('patients.index') }}" class="flex flex-wrap items-center gap-3 border-b border-line p-4" role="search">
            <label for="q" class="sr-only">Rechercher un patient</label>
            <div class="relative min-w-60 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Rechercher par nom, prénom, CIN ou téléphone"
                       class="field mt-0 h-11 pl-10">
            </div>

            <label for="genre" class="sr-only">Genre</label>
            <select id="genre" name="genre" class="field mt-0 h-11 w-auto" onchange="this.form.submit()">
                <option value="">Tous les genres</option>
                @foreach ($genders as $option)
                    <option value="{{ $option->value }}" @selected($gender === $option)>{{ $option === App\Enums\Gender::Female ? 'Femmes' : 'Hommes' }}</option>
                @endforeach
            </select>

            <label for="tri" class="sr-only">Trier</label>
            <select id="tri" name="tri" class="field mt-0 h-11 w-auto" onchange="this.form.submit()">
                <option value="nom" @selected($sort === 'nom')>Trier : nom</option>
                <option value="visite" @selected($sort === 'visite')>Trier : dernière visite</option>
            </select>

            <button type="submit" class="btn btn-primary">Rechercher</button>
            @if ($search !== '' || $gender)
                <a href="{{ route('patients.index') }}" class="link text-sm">Effacer</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>CIN</th>
                        <th>Âge</th>
                        <th>Téléphone</th>
                        <th>Dernière visite</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="avatar size-8 text-xs">{{ $patient->user->initials }}</span>
                                    <div class="leading-tight">
                                        <a href="{{ route('patients.show', $patient) }}" class="font-semibold text-brand hover:underline">
                                            {{ $patient->user->last_name }} {{ $patient->user->first_name }}
                                        </a>
                                        <div class="text-xs text-muted">{{ $patient->gender->label() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-ink-3">{{ $patient->cin ?? '—' }}</td>
                            <td class="text-ink-3">{{ $patient->age }} ans</td>
                            <td class="whitespace-nowrap text-ink-3">{{ $patient->user->phone }}</td>
                            <td class="whitespace-nowrap text-ink-3">
                                {{ $patient->last_visit_at ? Carbon\CarbonImmutable::parse($patient->last_visit_at)->translatedFormat('j F Y') : '—' }}
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @can('update', $patient)
                                    <a href="{{ route('patients.edit', $patient) }}" class="btn btn-secondary btn-sm">Modifier</a>
                                @endcan
                                @if (Route::has('appointments.create'))
                                    @can('create', App\Models\Appointment::class)
                                        <a href="{{ route('appointments.create', ['patient' => $patient->id]) }}" class="btn btn-secondary btn-sm">Rendez-vous</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-muted">
                                @if ($search !== '')
                                    Aucun patient ne correspond à « {{ $search }} ».
                                @else
                                    Aucun patient pour le moment.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm text-muted">
                <span>Patients {{ $patients->firstItem() }} à {{ $patients->lastItem() }} sur {{ $patients->total() }}</span>
                {{ $patients->onEachSide(1)->links('pagination.simple-pages') }}
            </div>
        @endif
    </div>
</x-app-layout>
