{{-- « Mes comptes rendus » du patient, en lecture seule (BF-11, RG-21). Variable : $consultations (paginées). --}}
<x-app-layout title="Mes comptes rendus" subtitle="Comptes rendus de vos consultations, en lecture seule.">
    <div class="space-y-4">
        @forelse ($consultations as $consultation)
            <article class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ ucfirst($consultation->consulted_at->translatedFormat('l j F Y')) }}</h2>
                    <span class="text-sm text-muted">{{ $consultation->appointment->doctor->display_name }} · {{ $consultation->appointment->doctor->specialty->name }}</span>
                </div>
                <dl class="grid grid-cols-3 gap-x-4 gap-y-2 p-5 text-sm">
                    <dt class="text-muted">Motif</dt>
                    <dd class="col-span-2">{{ $consultation->reason }}</dd>
                    <dt class="text-muted">Diagnostic</dt>
                    <dd class="col-span-2 font-semibold">{{ $consultation->diagnosis ?? '—' }}</dd>
                    <dt class="text-muted">Traitement</dt>
                    <dd class="col-span-2">
                        @foreach ($consultation->prescriptionItems as $item)
                            <p>{{ $item->medication }} {{ $item->dosage }} <span class="text-muted">— {{ $item->instructions() }}</span></p>
                        @endforeach
                        @if ($consultation->treatment)
                            <p class="mt-1">{{ $consultation->treatment }}</p>
                        @endif
                        @if ($consultation->prescriptionItems->isEmpty() && ! $consultation->treatment)
                            —
                        @endif
                    </dd>
                </dl>
                @if ($consultation->prescriptionItems->isNotEmpty())
                    <div class="border-t border-line px-5 py-3">
                        <a href="{{ route('consultations.prescription.pdf', $consultation) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Télécharger l'ordonnance (PDF)</a>
                    </div>
                @endif
            </article>
        @empty
            <p class="card p-6 text-center text-muted">Vos comptes rendus apparaîtront ici après vos consultations.</p>
        @endforelse

        @if ($consultations->hasPages())
            {{ $consultations->links('pagination.simple-pages') }}
        @endif
    </div>
</x-app-layout>
