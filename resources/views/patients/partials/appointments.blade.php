{{--
    Liste de rendez-vous sur la fiche patient ; chacun mène à sa fiche (actions, historique).
    Variables : $appointments (collection), $empty (texte si la liste est vide).
--}}
@if ($appointments->isEmpty())
    <p class="px-5 py-4 text-sm text-muted">{{ $empty }}</p>
@else
    <ul class="divide-y divide-line-soft">
        @foreach ($appointments as $appointment)
            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                <div>
                    <a href="{{ route('appointments.show', $appointment) }}" class="font-semibold text-ink hover:text-brand hover:underline">
                        {{ ucfirst($appointment->starts_at->translatedFormat('l j F Y')) }}
                        à {{ $appointment->starts_at->format('H:i') }}
                    </a>
                    <p class="text-muted">
                        {{ $appointment->reason ?? 'Sans motif' }} · {{ $appointment->doctor->display_name }}
                    </p>
                </div>
                <x-appointment-status :status="$appointment->status" />
            </li>
        @endforeach
    </ul>
@endif
