{{--
    Accueil du visiteur (maquette 1) : recherche d'un médecin par nom ou spécialité,
    prochain créneau libre de chacun, horaires et coordonnées du cabinet.
    Variables : $doctors, $nextSlots, $specialties, $specialty, $search, $settings.
--}}
<x-guest-layout title="Prendre rendez-vous en ligne" width="full">
    <section class="space-y-5">
        <div>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Prenez rendez-vous en ligne, 24 h/24.</h1>
            <p class="mt-3 text-muted">Choisissez un médecin, puis un créneau libre. Le secrétariat confirme votre demande par e-mail.</p>
        </div>

        <form method="GET" action="{{ route('home') }}" class="flex flex-wrap gap-3" role="search">
            @if ($specialty)
                <input type="hidden" name="specialite" value="{{ $specialty->id }}">
            @endif
            <label for="q" class="sr-only">Nom du médecin ou spécialité</label>
            <div class="relative min-w-60 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Nom du médecin ou spécialité" class="field mt-0 h-12 pl-10">
            </div>
            <button type="submit" class="btn btn-primary h-12">Rechercher</button>
        </form>

        <nav class="flex flex-wrap gap-2" aria-label="Spécialités">
            @foreach ([null, ...$specialties] as $option)
                @php($active = $option?->id === $specialty?->id)
                <a href="{{ route('home', array_filter(['q' => $search, 'specialite' => $option?->id])) }}" @class([
                    'rounded-full border px-4 py-2 text-sm font-medium',
                    'border-brand bg-brand text-white' => $active,
                    'border-line-strong bg-white text-ink-2 hover:border-brand hover:text-brand' => ! $active,
                ]) @if ($active) aria-current="page" @endif>{{ $option?->name ?? 'Toutes les spécialités' }}</a>
            @endforeach
        </nav>
    </section>

    <section class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3" aria-label="Nos médecins">
        @forelse ($doctors as $doctor)
            <x-doctor-card :doctor="$doctor" :next-slot="$nextSlots[$doctor->id]" />
        @empty
            <p class="card p-6 text-center text-muted md:col-span-2 lg:col-span-3">
                Aucun médecin ne correspond à votre recherche.
                <a href="{{ route('home') }}" class="link">Voir tous les médecins</a>
            </p>
        @endforelse
    </section>

    <section class="card mt-8 grid gap-5 p-6 text-sm sm:grid-cols-3">
        <div>
            <h2 class="font-semibold text-ink">Horaires d'ouverture</h2>
            <p class="text-ink-3">
                Lundi – vendredi {{ $settings->time('opening_time') }} – {{ $settings->time('closing_time') }}
                @if ($settings->isOpenOnSaturday())
                    · samedi {{ $settings->time('saturday_opening_time') }} – {{ $settings->time('saturday_closing_time') }}
                @endif
            </p>
        </div>
        <div>
            <h2 class="font-semibold text-ink">Adresse</h2>
            <p class="text-ink-3">{{ $settings->address ?? '—' }}</p>
        </div>
        <div>
            <h2 class="font-semibold text-ink">Téléphone</h2>
            <p class="text-ink-3">{{ $settings->phone ?? '—' }}</p>
        </div>
    </section>
</x-guest-layout>
