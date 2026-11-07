@props(['status'])

{{-- Pastille de couleur pour le statut d'un rendez-vous (App\Enums\AppointmentStatus). --}}
@php
    $classes = match ($status) {
        App\Enums\AppointmentStatus::Scheduled => 'bg-gray-100 text-gray-700',
        App\Enums\AppointmentStatus::Confirmed => 'bg-blue-50 text-blue-700',
        App\Enums\AppointmentStatus::Completed => 'bg-green-50 text-green-700',
        App\Enums\AppointmentStatus::Cancelled => 'bg-red-50 text-red-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $status->label() }}
</span>
