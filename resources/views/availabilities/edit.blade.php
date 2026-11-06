{{--
    Modification ou suppression d'une plage de la semaine type (BF-17).
    Variables : $availability, $days, $slotDurations, $settings.
--}}
<x-app-layout title="Modifier une plage" :subtitle="$availability->label()">
    <x-slot name="actions">
        <a href="{{ route('availabilities.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" /> Mes disponibilités</a>
    </x-slot>

    <div class="card max-w-3xl overflow-hidden">
        <form method="POST" action="{{ route('availabilities.update', $availability) }}">
            @csrf
            @method('PUT')

            <div class="space-y-4 p-5 sm:p-6">
                @include('availabilities.partials.fields')
                <p class="text-xs text-muted">
                    Les rendez-vous déjà pris ne changent pas : seuls les créneaux proposés ensuite tiennent compte de la modification.
                </p>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-line px-5 py-4 sm:px-6">
                <a href="{{ route('availabilities.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>

        @can('delete', $availability)
            <form method="POST" action="{{ route('availabilities.destroy', $availability) }}"
                  class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-subtle px-5 py-4 sm:px-6"
                  onsubmit="return confirm(@js("Supprimer la plage « {$availability->label()} » ?"))">
                @csrf
                @method('DELETE')
                <p class="text-sm text-muted">Cette plage ne sera plus proposée aux patients.</p>
                <button type="submit" class="btn btn-danger">Supprimer la plage</button>
            </form>
        @endcan
    </div>
</x-app-layout>
