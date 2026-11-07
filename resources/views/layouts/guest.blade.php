<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['pageTitle' => $title])
    </head>
    <body class="font-sans antialiased">
        <header class="border-b border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <x-brand />

                <nav class="flex items-center gap-1 text-sm" aria-label="Menu">
                    <a href="{{ url('/') }}" @class([
                        'rounded-lg px-3 py-2 font-medium',
                        'bg-brand-soft text-brand' => request()->is('/'),
                        'text-ink-3 hover:bg-hover hover:text-ink' => ! request()->is('/'),
                    ])>Nos médecins</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Mon espace</a>
                    @else
                        <a href="{{ route('login') }}" @class([
                            'rounded-lg px-3 py-2 font-medium',
                            'bg-brand-soft text-brand' => request()->routeIs('login'),
                            'text-ink-3 hover:bg-hover hover:text-ink' => ! request()->routeIs('login'),
                        ])>Connexion</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Créer un compte</a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main @class([
            'mx-auto px-4 py-10 sm:px-6',
            'max-w-md' => $width === 'md',
            'max-w-2xl' => $width === 'xl',
            'max-w-6xl' => $width === 'full',
        ])>
            @if ($title && $width !== 'full')
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-ink">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @if ($width === 'full')
                {{ $slot }}
            @else
                <div class="card p-6 sm:p-8">
                    {{ $slot }}
                </div>
            @endif
        </main>
    </body>
</html>
