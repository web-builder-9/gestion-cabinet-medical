@props(['status'])

{{-- Pastille de couleur d'un état de rendez-vous (mêmes couleurs que les maquettes). --}}
@php
    $classes = match ($status) {
        App\Enums\AppointmentStatus::Pending => 'bg-wait text-wait-ink',
        App\Enums\AppointmentStatus::Confirmed => 'bg-ok text-ok-ink',
        App\Enums\AppointmentStatus::Completed => 'bg-done text-done-ink',
        App\Enums\AppointmentStatus::Cancelled => 'bg-danger text-danger-ink',
        App\Enums\AppointmentStatus::NoShow => 'bg-neutral text-neutral-ink',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$classes}"]) }}>{{ $status->label() }}</span>
