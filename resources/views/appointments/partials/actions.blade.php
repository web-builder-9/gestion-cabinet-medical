{{--
    Boutons d'action d'un rendez-vous, affichés selon l'AppointmentPolicy (RG-17).
    Variables : $appointment ; $except (optionnel) : actions à ne pas afficher, ex. ['show'].
--}}
@php($except ??= [])

<div class="flex flex-wrap items-center justify-end gap-2">
    @can('confirm', $appointment)
        <form method="POST" action="{{ route('appointments.confirm', $appointment) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary btn-sm">Confirmer</button>
        </form>
    @endcan

    @can('update', $appointment)
        <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-secondary btn-sm">Déplacer</a>
    @endcan

    @can('markNoShow', $appointment)
        <form method="POST" action="{{ route('appointments.no-show', $appointment) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-secondary btn-sm">Absent</button>
        </form>
    @endcan

    @can('cancel', $appointment)
        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}"
              onsubmit="return confirm(@js("Annuler le rendez-vous de {$appointment->patient->full_name} ? Le créneau sera libéré."))">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-secondary btn-sm">Annuler</button>
        </form>
    @endcan

    @unless (in_array('show', $except, true))
        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary btn-sm" aria-label="Voir le rendez-vous de {{ $appointment->patient->full_name }}">
            <x-icon name="chevron-right" class="size-4" />
        </a>
    @endunless
</div>
