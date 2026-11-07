{{--
    Consultations précédentes d'un patient, tous médecins (BF-20).
    Variables : $history (consultations avec appointment.doctor.user et prescriptionItems), $patient.
--}}
<ul class="divide-y divide-line-soft">
    @forelse ($history as $previous)
        <li class="space-y-1 px-5 py-4 text-sm">
            <p class="font-semibold text-ink">{{ $previous->consulted_at->translatedFormat('j F Y') }}</p>
            <p class="text-ink-3">
                {{ $previous->diagnosis ?? $previous->reason }}
                @unless ($previous->appointment->doctor->user_id === Auth::id())
                    <span class="text-muted">({{ $previous->appointment->doctor->display_name }})</span>
                @endunless
            </p>
            <p class="flex flex-wrap gap-x-4">
                <a href="{{ route('consultations.show', $previous) }}" class="link">Compte rendu</a>
                @if ($previous->prescriptionItems->isNotEmpty())
                    <a href="{{ route('consultations.prescription', $previous) }}" class="link">Ordonnance</a>
                @endif
            </p>
        </li>
    @empty
        <li class="px-5 py-6 text-center text-sm text-muted">Première consultation au cabinet.</li>
    @endforelse
</ul>
