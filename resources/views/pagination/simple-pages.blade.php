{{-- Pagination des maquettes : ‹ 1 2 3 … 31 ›. Utilisation : $liste->links('pagination.simple-pages') --}}
@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" aria-label="{{ __('Pagination Navigation') }}">
        @php($base = 'inline-flex size-9 items-center justify-center rounded-lg border text-sm font-medium')

        @if ($paginator->onFirstPage())
            <span class="{{ $base }} border-line text-line-strong" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} border-line-strong bg-white text-ink-2 hover:bg-canvas" aria-label="Page précédente"><x-icon name="chevron-left" class="size-4" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-muted">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="{{ $base }} border-brand bg-brand text-white" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $base }} border-line-strong bg-white text-ink-2 hover:bg-canvas">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} border-line-strong bg-white text-ink-2 hover:bg-canvas" aria-label="Page suivante"><x-icon name="chevron-right" class="size-4" /></a>
        @else
            <span class="{{ $base }} border-line text-line-strong" aria-disabled="true"><x-icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
