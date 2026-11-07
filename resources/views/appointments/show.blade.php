{{--
    Fiche d'un rendez-vous : informations, actions possibles et historique des états (RG-23, BF-30).
--}}
<x-app-layout :title="'Rendez-vous du '.$appointment->starts_at->translatedFormat('l j F Y')"
              :subtitle="$appointment->starts_at->format('H:i').' – '.$appointment->ends_at->format('H:i').' · '.$appointment->doctor->display_name">
    <x-slot name="actions">
        <a href="{{ route('agenda', ['medecin' => $appointment->doctor_id, 'date' => $appointment->starts_at->format('Y-m-d')]) }}" class="btn btn-secondary">
            <x-icon name="calendar" /> Voir dans l'agenda
        </a>
        {{-- Compte rendu : médecins uniquement (RG-21). --}}
        @if ($appointment->consultation && Auth::user()->can('view', $appointment->consultation))
            <a href="{{ route('consultations.show', $appointment->consultation) }}" class="btn btn-primary"><x-icon name="consultation" /> Compte rendu</a>
        @elsecan('create', [App\Models\Consultation::class, $appointment])
            <a href="{{ route('consultations.create', $appointment) }}" class="btn btn-primary"><x-icon name="consultation" /> Commencer la consultation</a>
        @endif
    </x-slot>

    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Rendez-vous</h2>
                <x-appointment-status :status="$appointment->status" />
            </div>
            <dl class="grid grid-cols-3 gap-x-4 gap-y-3 p-5 text-sm">
                <dt class="text-muted">Patient</dt>
                <dd class="col-span-2">
                    <a href="{{ route('patients.show', $appointment->patient) }}" class="link">{{ $appointment->patient->full_name }}</a>
                    <span class="text-muted">· {{ $appointment->patient->user->phone }}</span>
                </dd>
                <dt class="text-muted">Médecin</dt>
                <dd class="col-span-2">{{ $appointment->doctor->display_name }} · {{ $appointment->doctor->specialty->name }}</dd>
                <dt class="text-muted">Créneau</dt>
                <dd class="col-span-2">
                    {{ ucfirst($appointment->starts_at->translatedFormat('l j F Y')) }},
                    {{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at->format('H:i') }}
                </dd>
                <dt class="text-muted">Motif</dt>
                <dd class="col-span-2">{{ $appointment->reason ?? '—' }}</dd>
                <dt class="text-muted">Notes pour le médecin</dt>
                <dd class="col-span-2 whitespace-pre-line">{{ $appointment->notes ?? '—' }}</dd>
            </dl>
            <div class="border-t border-line px-5 py-4">
                @include('appointments.partials.actions', ['except' => ['show']])
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Historique</h2></div>
            <ol class="space-y-4 p-5">
                @foreach ($appointment->statusChanges as $change)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 size-2 flex-none rounded-full bg-brand" aria-hidden="true"></span>
                        <div>
                            <p class="text-ink">
                                @if ($change->old_status)
                                    {{ $change->old_status->label() }} → <strong class="font-semibold">{{ $change->new_status->label() }}</strong>
                                @else
                                    Créé <strong class="font-semibold">{{ mb_strtolower($change->new_status->label()) }}</strong>
                                @endif
                            </p>
                            <p class="text-muted">
                                {{ App\Support\Dates::short($change->changed_at) }}
                                · {{ $change->author ? $change->author->full_name.' ('.mb_strtolower($change->author->role->label()).')' : 'automatique' }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-app-layout>
