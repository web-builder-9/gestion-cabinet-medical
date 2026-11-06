{{--
    Pages d'erreur (maquette 4, BNF-07). Aucune requête en base ici :
    la page doit s'afficher même si la base de données est indisponible.
    Variables : $code, $title, $message, $icon, $tone (« danger » ou « neutral »).
--}}
<!DOCTYPE html>
<html lang="fr">
    <head>
        @include('layouts.partials.head', ['pageTitle' => $title])
    </head>
    <body class="font-sans antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="card w-full max-w-lg p-8 sm:p-10">
                <span @class([
                    'inline-flex size-12 items-center justify-center rounded-xl',
                    'bg-danger text-danger-ink' => $tone === 'danger',
                    'bg-neutral text-ink-3' => $tone !== 'danger',
                ])>
                    <x-icon :name="$icon" class="size-6" />
                </span>

                <p @class([
                    'mt-5 text-6xl font-bold tracking-tight',
                    'text-danger-ink' => $tone === 'danger',
                    'text-ink-2' => $tone !== 'danger',
                ])>{{ $code }}</p>
                <h1 class="mt-2 text-2xl font-bold text-ink">{{ $title }}</h1>
                <p class="mt-3 text-sm leading-relaxed text-ink-3">{{ $message }}</p>

                <div class="mt-7">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Retour au tableau de bord</a>
                    @else
                        <a href="{{ url('/') }}" class="btn btn-secondary">Aller à l'accueil</a>
                    @endauth
                </div>
            </div>
        </main>
    </body>
</html>
