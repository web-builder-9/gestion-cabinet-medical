@props(['doctor', 'nextSlot' => null])

{{-- Carte d'un médecin (maquettes 1 et 5) : spécialité, présentation, prochain créneau libre. --}}
<article {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-6']) }}>
    <div class="flex items-center gap-4">
        <span class="avatar size-14 text-lg">{{ $doctor->user->initials }}</span>
        <div>
            <h2 class="text-lg font-bold text-ink">{{ $doctor->display_name }}</h2>
            <p class="text-sm font-semibold text-brand">{{ $doctor->specialty->name }}</p>
        </div>
    </div>
    @if ($doctor->presentation)
        <p class="text-sm text-ink-3">{{ $doctor->presentation }}</p>
    @endif
    <p class="mt-auto rounded-lg bg-subtle px-4 py-3 text-sm">
        @if ($nextSlot)
            Prochain créneau : <strong class="font-semibold">{{ $nextSlot->start->translatedFormat('l j M') }} à {{ $nextSlot->label() }}</strong>
        @else
            Aucun créneau libre pour le moment.
        @endif
    </p>
    <a href="{{ route('booking.create', ['medecin' => $doctor->id]) }}" class="btn btn-primary w-full">Prendre rendez-vous</a>
</article>
