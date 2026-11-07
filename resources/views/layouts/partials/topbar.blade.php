{{-- Barre du haut de l'espace patient (maquettes 5 et 6). --}}
<header x-data="{ open: false }" class="relative border-b border-line bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <x-brand :subtitle="App\Support\Navigation::areaName($user)" />

        <button type="button" class="btn btn-secondary btn-sm md:hidden" @click="open = ! open" aria-label="Menu">
            <x-icon name="menu" />
        </button>

        <nav :class="open ? 'flex' : 'hidden'" class="absolute inset-x-0 top-16 z-20 flex-col gap-1 border-b border-line bg-white p-3 md:static md:flex md:flex-row md:items-center md:border-0 md:p-0" aria-label="Menu principal">
            @foreach (App\Support\Navigation::items($user) as $item)
                @php($active = request()->routeIs($item['active']))
                <a href="{{ route($item['route']) }}" @class([
                    'rounded-lg px-3 py-2 text-sm',
                    'bg-brand-soft font-semibold text-brand' => $active,
                    'font-medium text-ink-3 hover:bg-hover hover:text-ink' => ! $active,
                ]) @if ($active) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('profile.edit') }}" @class([
                'rounded-lg px-3 py-2 text-sm',
                'bg-brand-soft font-semibold text-brand' => request()->routeIs('profile.*'),
                'font-medium text-ink-3 hover:bg-hover hover:text-ink' => ! request()->routeIs('profile.*'),
            ])>Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-ink-3 hover:bg-hover hover:text-ink">Déconnexion</button>
            </form>
        </nav>
    </div>
</header>
