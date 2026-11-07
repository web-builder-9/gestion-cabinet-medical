{{--
    Liste des rendez-vous du cabinet (BF-14, BF-15) avec filtres.
    Variables : $appointments (paginés), $doctors, $statuses, $period, $status, $doctorId, $search, $pendingCount.
--}}
<x-app-layout title="Rendez-vous" :subtitle="trans_choice(':count rendez-vous|:count rendez-vous', $appointments->total())">
    <x-slot name="actions">
        <a href="{{ route('agenda') }}" class="btn btn-secondary"><x-icon name="calendar" /> Planning de la semaine</a>
        @can('create', App\Models\Appointment::class)
            <a href="{{ route('appointments.create') }}" class="btn btn-primary"><x-icon name="plus" /> Nouveau rendez-vous</a>
        @endcan
    </x-slot>

    <x-flash-status />

    <div class="card overflow-hidden">
        <form method="GET" action="{{ route('appointments.index') }}" class="space-y-3 border-b border-line p-4" role="search">
            <div class="flex flex-wrap items-center gap-3">
                <nav class="segmented" aria-label="Période">
                    @foreach (['aujourdhui' => 'Aujourd\'hui', 'a-venir' => 'À venir', 'passes' => 'Passés'] as $value => $label)
                        <a href="{{ route('appointments.index', ['periode' => $value]) }}" @if ($period === $value) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                @if ($pendingCount > 0)
                    <a href="{{ route('appointments.index', ['etat' => 'pending']) }}" class="badge bg-wait text-wait-ink hover:underline">{{ $pendingCount }} à confirmer</a>
                @endif
            </div>

            <input type="hidden" name="periode" value="{{ $period }}">
            <div class="flex flex-wrap items-center gap-3">
                <label for="q" class="sr-only">Patient</label>
                <div class="relative min-w-56 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Rechercher un patient" class="field mt-0 h-11 pl-10">
                </div>

                <label for="medecin" class="sr-only">Médecin</label>
                <select id="medecin" name="medecin" class="field mt-0 h-11 w-auto" onchange="this.form.submit()">
                    <option value="">Tous les médecins</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected($doctorId === $doctor->id)>{{ $doctor->display_name }}</option>
                    @endforeach
                </select>

                <label for="etat" class="sr-only">État</label>
                <select id="etat" name="etat" class="field mt-0 h-11 w-auto" onchange="this.form.submit()">
                    <option value="">Tous les états</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">Filtrer</button>
                @if ($search !== '' || $status || $doctorId)
                    <a href="{{ route('appointments.index', ['periode' => $period]) }}" class="link text-sm">Effacer</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Créneau</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Motif</th>
                        <th>État</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="font-semibold text-ink">{{ ucfirst($appointment->starts_at->translatedFormat('D j M')) }}</span>
                                <span class="tabular-nums text-ink-3">· {{ $appointment->starts_at->format('H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('patients.show', $appointment->patient) }}" class="font-semibold text-brand hover:underline">{{ $appointment->patient->full_name }}</a>
                            </td>
                            <td class="whitespace-nowrap text-ink-3">{{ $appointment->doctor->display_name }}</td>
                            <td class="text-ink-3">{{ $appointment->reason ?? '—' }}</td>
                            <td><x-appointment-status :status="$appointment->status" /></td>
                            <td>@include('appointments.partials.actions')</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-muted">Aucun rendez-vous ne correspond à ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($appointments->hasPages())
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm text-muted">
                <span>Rendez-vous {{ $appointments->firstItem() }} à {{ $appointments->lastItem() }} sur {{ $appointments->total() }}</span>
                {{ $appointments->onEachSide(1)->links('pagination.simple-pages') }}
            </div>
        @endif
    </div>
</x-app-layout>
