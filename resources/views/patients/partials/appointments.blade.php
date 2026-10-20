{{--
    Liste de rendez-vous en lecture seule sur la fiche patient.
    Variables : $appointments (collection), $empty (texte si la liste est vide).
--}}
@if ($appointments->isEmpty())
    <p class="text-sm text-gray-500">{{ $empty }}</p>
@else
    <ul class="divide-y divide-gray-100">
        @foreach ($appointments as $appointment)
            <li class="py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                <div>
                    <p class="font-medium text-gray-900">
                        {{ ucfirst($appointment->starts_at->translatedFormat('l j F Y')) }}
                        à {{ $appointment->starts_at->format('H:i') }}
                    </p>
                    <p class="text-gray-500">
                        {{ $appointment->reason ?? 'Sans motif' }} · {{ $appointment->doctor->name }}
                    </p>
                </div>
                <x-appointment-status :status="$appointment->status" />
            </li>
        @endforeach
    </ul>
@endif
