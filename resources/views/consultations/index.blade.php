{{--
    Consultations du médecin : rendez-vous du jour (commencer la consultation)
    et derniers comptes rendus. Variables : $today, $recent.
--}}
<x-app-layout title="Consultations" :subtitle="ucfirst(today()->translatedFormat('l j F Y'))">
    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Patients du jour</h2>
                <a href="{{ route('agenda', ['vue' => 'jour']) }}" class="link text-sm">Agenda du jour</a>
            </div>
            <ul class="divide-y divide-line-soft">
                @forelse ($today as $appointment)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <span class="w-12 font-semibold tabular-nums">{{ $appointment->starts_at->format('H:i') }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('patients.show', $appointment->patient) }}" class="font-semibold text-ink hover:text-brand hover:underline">{{ $appointment->patient->full_name }}</a>
                            <p class="text-sm text-muted">{{ $appointment->reason ?? 'Sans motif' }}</p>
                        </div>
                        <x-appointment-status :status="$appointment->status" />
                        @if ($appointment->consultation)
                            <a href="{{ route('consultations.show', $appointment->consultation) }}" class="btn btn-secondary btn-sm">Compte rendu</a>
                        @elsecan('create', [App\Models\Consultation::class, $appointment])
                            <a href="{{ route('consultations.create', $appointment) }}" class="btn btn-primary btn-sm">Commencer</a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Aucun patient confirmé aujourd'hui.</li>
                @endforelse
            </ul>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Derniers comptes rendus</h2></div>
            <ul class="divide-y divide-line-soft">
                @forelse ($recent as $consultation)
                    <li>
                        <a href="{{ route('consultations.show', $consultation) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-subtle">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $consultation->appointment->patient->full_name }}</p>
                                <p class="truncate text-sm text-muted">{{ $consultation->diagnosis ?? $consultation->reason }}</p>
                            </div>
                            <span class="text-sm whitespace-nowrap text-muted">{{ App\Support\Dates::short($consultation->consulted_at, false) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Aucune consultation enregistrée.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
