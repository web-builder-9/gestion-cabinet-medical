{{-- Menu latéral du personnel. Sur mobile, il s'ouvre par-dessus la page (Alpine : « menu »). --}}
<div x-show="menu" x-cloak class="fixed inset-0 z-30 bg-ink/40 lg:hidden" @click="menu = false"></div>

<aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col gap-1 border-r border-line bg-white px-3.5 py-5 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
    <div class="flex items-center justify-between px-2 pb-5">
        <x-brand :subtitle="App\Support\Navigation::areaName($user)" />
        <button type="button" class="lg:hidden" @click="menu = false" aria-label="Fermer le menu">
            <x-icon name="close" class="text-muted" />
        </button>
    </div>

    <nav class="flex flex-col gap-1" aria-label="Menu principal">
        @foreach (App\Support\Navigation::items($user) as $item)
            @php($active = request()->routeIs($item['active']))
            <a href="{{ route($item['route']) }}" @class([
                'flex min-h-11 items-center gap-2.5 rounded-lg px-3 text-sm',
                'bg-brand-soft font-semibold text-brand' => $active,
                'font-medium text-ink-3 hover:bg-hover hover:text-ink' => ! $active,
            ]) @if ($active) aria-current="page" @endif>
                <x-icon :name="$item['icon']" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto flex flex-col gap-1 border-t border-line pt-3">
        <a href="{{ route('profile.edit') }}" @class([
            'flex min-h-11 items-center gap-2.5 rounded-lg px-3 text-sm',
            'bg-brand-soft font-semibold text-brand' => request()->routeIs('profile.*'),
            'font-medium text-ink-3 hover:bg-hover hover:text-ink' => ! request()->routeIs('profile.*'),
        ])>
            <x-icon name="user" /> Mon profil
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex min-h-11 w-full items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-ink-3 hover:bg-hover hover:text-ink">
                <x-icon name="logout" /> Déconnexion
            </button>
        </form>
    </div>
</aside>
