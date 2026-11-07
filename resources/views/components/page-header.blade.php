@props(['title', 'subtitle' => null])

{{-- En-tête de page : titre, sous-titre et boutons d'action (maquettes). --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
