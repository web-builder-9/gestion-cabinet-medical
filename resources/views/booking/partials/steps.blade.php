{{-- Étapes de la prise de rendez-vous (maquette 5). Variable : $current (1, 2 ou 3). --}}
<ol class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm" aria-label="Étapes">
    @foreach ([1 => 'Médecin', 2 => 'Créneau', 3 => 'Confirmation'] as $number => $label)
        <li class="flex items-center gap-2" @if ($number === $current) aria-current="step" @endif>
            <span @class([
                'inline-flex size-7 items-center justify-center rounded-full text-xs font-bold',
                'bg-brand-soft text-brand' => $number < $current,
                'bg-brand text-white' => $number === $current,
                'border border-line-strong text-muted' => $number > $current,
            ])>
                @if ($number < $current)
                    <x-icon name="check" class="size-4" />
                @else
                    {{ $number }}
                @endif
            </span>
            <span @class(['font-semibold text-ink' => $number === $current, 'text-muted' => $number !== $current])>{{ $label }}</span>
        </li>
    @endforeach
</ol>
