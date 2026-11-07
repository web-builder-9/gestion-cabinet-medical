{{--
    Tableau de bord du secrétariat (maquette 7) : chiffres du jour, demandes en ligne
    à confirmer, rendez-vous à déplacer et planning du jour de tous les médecins (BF-15).
--}}
<x-app-layout :title="'Bonjour '.Auth::user()->first_name"
              :subtitle="ucfirst($today->translatedFormat('l j F Y')).' · planning du jour des '.trans_choice(':count médecin|:count médecins', $doctors->count())">
    <x-slot name="actions">
        @can('create', App\Models\Appointment::class)
            <a href="{{ route('appointments.create') }}" class="btn btn-primary"><x-icon name="plus" /> Nouveau rendez-vous</a>
        @endcan
    </x-slot>

    <x-flash-status />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-muted">Rendez-vous aujourd'hui</p>
            <p class="stat-value mt-1">{{ $todayCount }}</p>
            <p class="stat-label mt-1">{{ $todayCompleted }} {{ $todayCompleted > 1 ? 'terminés' : 'terminé' }} · {{ $todayUpcoming }} à venir</p>
        </div>
        <a href="{{ route('appointments.index', ['etat' => 'pending']) }}" class="card p-5 hover:border-line-strong">
            <p class="text-sm text-muted">À confirmer</p>
            <p class="stat-value mt-1 text-wait-ink">{{ $pendingCount }}</p>
            <p class="stat-label mt-1">demandes prises en ligne</p>
        </a>
        <div class="card p-5">
            <p class="text-sm text-muted">Absences en {{ $today->translatedFormat('F') }}</p>
            <p class="stat-value mt-1">{{ $month['no_shows'] }}</p>
            <p class="stat-label mt-1">sur {{ $month['appointments'] }} rendez-vous</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-muted">Nouveaux patients</p>
            <p class="stat-value mt-1">{{ $newPatients }}</p>
            <p class="stat-label mt-1">depuis le 1er {{ $today->translatedFormat('F') }}</p>
        </div>
    </div>

    @if ($toMove->isNotEmpty())
        <section class="card overflow-hidden border-danger-line">
            <div class="card-header bg-danger/40">
                <h2 class="card-title flex items-center gap-2 text-danger-ink"><x-icon name="warning" /> Rendez-vous à déplacer</h2>
                <p class="text-sm text-danger-ink">Le médecin a déclaré une indisponibilité sur ces créneaux.</p>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <tbody>
                        @foreach ($toMove as $appointment)
                            <tr>
                                <td class="font-semibold">{{ $appointment->patient->full_name }}</td>
                                <td>{{ $appointment->doctor->display_name }}</td>
                                <td class="whitespace-nowrap">{{ App\Support\Dates::short($appointment->starts_at) }}</td>
                                <td><x-appointment-status :status="$appointment->status" /></td>
                                <td>@include('appointments.partials.actions', ['except' => ['show']])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Demandes en ligne à confirmer</h2>
            @if ($pendingCount > 0)
                <a href="{{ route('appointments.index', ['etat' => 'pending']) }}" class="link text-sm">Voir les {{ $pendingCount }}</a>
            @endif
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Créneau demandé</th>
                        <th>Reçue</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingAppointments as $appointment)
                        <tr>
                            <td class="font-semibold whitespace-nowrap">{{ $appointment->patient->full_name }}</td>
                            <td class="whitespace-nowrap">Dr {{ $appointment->doctor->user->last_name }}</td>
                            <td class="whitespace-nowrap">{{ $appointment->starts_at->translatedFormat('D j M') }} · {{ $appointment->starts_at->format('H:i') }}</td>
                            <td class="whitespace-nowrap text-muted">{{ App\Support\Dates::short($appointment->created_at) }}</td>
                            <td>@include('appointments.partials.actions', ['except' => ['show']])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-muted">Aucune demande à confirmer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Planning du jour</h2>
            <a href="{{ route('agenda') }}" class="link text-sm">Planning de la semaine</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        @foreach ($doctors as $doctor)
                            <th>{{ $doctor->display_name }} · {{ $doctor->specialty->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($planning['times'] as $time)
                        <tr>
                            <td class="font-semibold tabular-nums">{{ $time }}</td>
                            @foreach ($doctors as $doctor)
                                @php($cell = $planning['cells'][$doctor->id][$time] ?? null)
                                <td class="whitespace-nowrap">
                                    @if ($cell['appointment'] ?? null)
                                        <a href="{{ route('appointments.show', $cell['appointment']) }}" class="mr-1 hover:underline">{{ $cell['appointment']->patient->user->short_name }}</a>
                                        <x-appointment-status :status="$cell['appointment']->status" />
                                    @elseif (($cell['state'] ?? null) === App\Services\Slot::FREE)
                                        <a href="{{ route('appointments.create', ['medecin' => $doctor->id, 'date' => $today->format('Y-m-d')]) }}" class="text-muted hover:text-brand">Libre</a>
                                    @elseif (($cell['state'] ?? null) === App\Services\Slot::UNAVAILABLE)
                                        <span class="text-muted">Indisponible</span>
                                    @else
                                        <span class="text-line-strong" aria-hidden="true">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $doctors->count() + 1 }}" class="py-6 text-center text-muted">Aucune consultation prévue aujourd'hui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
