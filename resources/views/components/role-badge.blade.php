@props(['role'])

{{-- Pastille de couleur d'un rôle (maquette 16). --}}
@php
    $classes = match ($role) {
        App\Enums\Role::Doctor => 'bg-brand-soft text-brand',
        App\Enums\Role::Secretary => 'bg-ok text-ok-ink',
        App\Enums\Role::Admin => 'bg-admin text-admin-ink',
        App\Enums\Role::Patient => 'bg-neutral text-neutral-ink',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$classes}"]) }}>{{ $role->label() }}</span>
