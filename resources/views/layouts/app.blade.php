<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['pageTitle' => $title])
    </head>
    <body class="font-sans antialiased">
        @php($user = Auth::user())

        @if ($user->isPatient())
            {{-- Espace patient (maquettes 5 et 6) : barre de navigation en haut. --}}
            @include('layouts.partials.topbar', ['user' => $user])

            <main class="mx-auto flex max-w-6xl flex-col gap-5 px-4 py-8 sm:px-6">
                @if ($title)
                    <x-page-header :title="$title" :subtitle="$subtitle">
                        @isset($actions)
                            <x-slot name="actions">{{ $actions }}</x-slot>
                        @endisset
                    </x-page-header>
                @endif

                {{ $slot }}
            </main>
        @else
            {{-- Personnel (maquettes 7 à 17) : menu latéral, réductible sur mobile. --}}
            <div x-data="{ menu: false }" class="min-h-screen lg:flex">
                @include('layouts.partials.sidebar', ['user' => $user])

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between border-b border-line bg-white px-4 py-3 lg:hidden">
                        <x-brand :subtitle="App\Support\Navigation::areaName($user)" />
                        <button type="button" class="btn btn-secondary btn-sm" @click="menu = true" aria-label="Ouvrir le menu">
                            <x-icon name="menu" />
                        </button>
                    </div>

                    <main class="flex flex-col gap-5 px-4 py-6 sm:px-8 sm:py-8">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            @if ($title)
                                <x-page-header :title="$title" :subtitle="$subtitle" class="flex-1">
                                    @isset($actions)
                                        <x-slot name="actions">{{ $actions }}</x-slot>
                                    @endisset
                                </x-page-header>
                            @endif

                            <div class="hidden items-center gap-2.5 sm:flex">
                                <span class="avatar">{{ $user->initials }}</span>
                                <span class="leading-tight">
                                    <span class="block text-sm font-semibold">{{ $user->doctor ? $user->doctor->display_name : $user->full_name }}</span>
                                    <span class="block text-xs text-muted">
                                        {{ $user->role->label() }}@if ($user->doctor) · {{ $user->doctor->specialty->name }}@endif
                                    </span>
                                </span>
                            </div>
                        </div>

                        {{ $slot }}
                    </main>
                </div>
            </div>
        @endif
    </body>
</html>
