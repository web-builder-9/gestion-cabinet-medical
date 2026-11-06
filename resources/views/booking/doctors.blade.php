{{-- Prise de rendez-vous, étape 1 : choisir le médecin (BF-01, BF-07). Variables : $doctors, $nextSlots. --}}
<x-app-layout title="Prendre un rendez-vous">
    <x-slot name="actions">
        @include('booking.partials.steps', ['current' => 1])
    </x-slot>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($doctors as $doctor)
            <x-doctor-card :doctor="$doctor" :next-slot="$nextSlots[$doctor->id]" />
        @empty
            <p class="card p-6 text-center text-muted md:col-span-2 lg:col-span-3">Aucun médecin ne prend de rendez-vous pour le moment.</p>
        @endforelse
    </div>
</x-app-layout>
