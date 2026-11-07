@props(['appointment', 'top', 'height'])

{{-- Un rendez-vous dans l'agenda (maquette 11) : couleur selon l'état, annulé barré. --}}
@php
    $classes = match ($appointment->status) {
        App\Enums\AppointmentStatus::Pending => 'border-wait-ink/30 bg-wait text-wait-ink',
        App\Enums\AppointmentStatus::Confirmed => 'border-ok-ink/25 bg-ok text-ok-ink',
        App\Enums\AppointmentStatus::Completed => 'border-done-ink/25 bg-done text-done-ink',
        App\Enums\AppointmentStatus::Cancelled => 'border-danger-ink/25 bg-danger text-danger-ink line-through',
        App\Enums\AppointmentStatus::NoShow => 'border-line-strong bg-neutral text-neutral-ink',
    };
    // Rendez-vous long : on affiche aussi le motif.
    $long = $height >= 60;
@endphp

<a href="{{ route('appointments.show', $appointment) }}"
   style="top: {{ $top + 2 }}px; height: {{ $height }}px"
   class="absolute inset-x-1 z-10 overflow-hidden rounded-md border px-2 py-1 text-xs hover:shadow-sm {{ $classes }}"
   title="{{ $appointment->starts_at->format('H:i') }} {{ $appointment->patient->full_name }} · {{ $appointment->status->label() }}">
    <span class="font-semibold tabular-nums">{{ $appointment->starts_at->format('H:i') }}</span>
    <span class="font-medium">{{ $appointment->patient->user->short_name }}</span>
    @if ($long && $appointment->reason)
        <span class="block truncate opacity-80">{{ $appointment->reason }}</span>
    @endif
</a>
