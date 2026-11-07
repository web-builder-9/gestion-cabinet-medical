@props(['subtitle' => null])

{{-- Logo + nom du cabinet, avec un sous-titre (« Secrétariat », « Espace patient · … »). --}}
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <x-application-logo />
    <span class="leading-tight">
        <span class="block text-[15px] font-bold text-ink">{{ App\Models\Setting::current()->cabinet_name }}</span>
        <span class="block text-xs text-muted">{{ $subtitle ?? 'Djerba' }}</span>
    </span>
</a>
