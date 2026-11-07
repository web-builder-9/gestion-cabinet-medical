{{--
    Compte rendu d'une consultation, en lecture (BF-20) ; modifiable par son auteur (RG-20).
    Variable : $consultation (avec appointment.patient.user, appointment.doctor.user, prescriptionItems).
--}}
@php($appointment = $consultation->appointment)

<x-app-layout :title="'Consultation du '.$consultation->consulted_at->translatedFormat('j F Y')"
              :subtitle="$appointment->doctor->display_name.' · '.$appointment->patient->full_name">
    <x-slot name="actions">
        @if ($consultation->prescriptionItems->isNotEmpty())
            <a href="{{ route('consultations.prescription', $consultation) }}" class="btn btn-secondary"><x-icon name="printer" /> Ordonnance</a>
        @endif
        @can('update', $consultation)
            <a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-primary"><x-icon name="edit" /> Modifier</a>
        @endcan
    </x-slot>

    <x-flash-status />

    @include('consultations.partials.patient-header', ['patient' => $appointment->patient, 'record' => $appointment->patient->medicalRecord])

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Compte rendu</h2>
                @cannot('update', $consultation)
                    <span class="text-xs text-muted">Lecture seule</span>
                @endcannot
            </div>
            <dl class="grid grid-cols-3 gap-x-4 gap-y-3 p-5 text-sm">
                <dt class="text-muted">Motif</dt>
                <dd class="col-span-2">{{ $consultation->reason }}</dd>
                <dt class="text-muted">Observations</dt>
                <dd class="col-span-2 whitespace-pre-line">{{ $consultation->observations ?? '—' }}</dd>
                <dt class="text-muted">Diagnostic</dt>
                <dd class="col-span-2 font-semibold">{{ $consultation->diagnosis ?? '—' }}</dd>
                <dt class="text-muted">Recommandations</dt>
                <dd class="col-span-2 whitespace-pre-line">{{ $consultation->treatment ?? '—' }}</dd>
            </dl>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Traitement prescrit</h2></div>
            <ol class="divide-y divide-line-soft">
                @forelse ($consultation->prescriptionItems as $item)
                    <li class="flex gap-3 px-5 py-3 text-sm">
                        <span class="inline-flex size-6 flex-none items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand">{{ $item->position }}</span>
                        <div>
                            <p class="font-semibold text-ink">{{ $item->medication }} {{ $item->dosage }}</p>
                            <p class="text-ink-3">{{ $item->instructions() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Aucun médicament prescrit.</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-app-layout>
