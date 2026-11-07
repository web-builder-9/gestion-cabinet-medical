{{--
    Spécialités et paramètres (maquette 17) : spécialités (BF-25), activité du mois (BF-27)
    et paramètres du cabinet (BF-26).
    Les formulaires des spécialités rangent leurs erreurs dans le sac « specialty »
    pour ne pas les mélanger avec celles des paramètres.
--}}
<x-app-layout title="Spécialités et paramètres" subtitle="Ces réglages s'appliquent à tout le cabinet">
    <x-flash-status />

    @php($specialtyErrors = $errors->specialty)

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
        <div class="flex flex-col gap-5">
            <section class="card overflow-hidden">
                <div class="card-header"><h2 class="card-title">Spécialités</h2></div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Spécialité</th>
                                <th>Médecins</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($specialties as $specialty)
                                {{-- « Modifier » ouvre la ligne en mode édition (Alpine.js) ; elle reste ouverte après une erreur. --}}
                                @php($editingThis = (string) old('specialty_form') === (string) $specialty->id && $specialtyErrors->any())
                                <tr x-data="{ editing: @js($editingThis) }">
                                    <td x-show="! editing" class="font-semibold text-ink">{{ $specialty->name }}</td>
                                    <td x-show="! editing" class="tabular-nums text-ink-3">{{ $specialty->doctors_count }}</td>
                                    <td x-show="! editing" class="text-right whitespace-nowrap">
                                        @can('update', $specialty)
                                            <button type="button" class="btn btn-secondary btn-sm" x-on:click="editing = true" aria-label="Modifier la spécialité {{ $specialty->name }}">Modifier</button>
                                        @endcan
                                        @can('delete', $specialty)
                                            <form method="POST" action="{{ route('admin.specialties.destroy', $specialty) }}" class="inline"
                                                  onsubmit="return confirm(@js("Supprimer la spécialité « {$specialty->name} » ?"))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" aria-label="Supprimer la spécialité {{ $specialty->name }}">Supprimer</button>
                                            </form>
                                        @endcan
                                    </td>

                                    <td x-show="editing" x-cloak colspan="3">
                                        <form method="POST" action="{{ route('admin.specialties.update', $specialty) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="specialty_form" value="{{ $specialty->id }}">

                                            <div>
                                                <label for="specialty-{{ $specialty->id }}-name" class="label">Spécialité</label>
                                                <input id="specialty-{{ $specialty->id }}-name" name="name" type="text" required maxlength="100"
                                                       value="{{ $editingThis ? old('name') : $specialty->name }}"
                                                       @class(['field', 'field-error' => $editingThis && $specialtyErrors->has('name')])>
                                                @if ($editingThis)
                                                    <x-input-error class="mt-2" :messages="$specialtyErrors->get('name')" />
                                                @endif
                                            </div>
                                            <div>
                                                <label for="specialty-{{ $specialty->id }}-description" class="label">Description (page d'accueil)</label>
                                                <input id="specialty-{{ $specialty->id }}-description" name="description" type="text" maxlength="500"
                                                       value="{{ $editingThis ? old('description') : $specialty->description }}" class="field">
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="btn btn-secondary btn-sm" x-on:click="editing = false">Annuler</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4">
                    <p class="text-sm text-muted">Une spécialité attribuée à un médecin ne peut pas être supprimée.</p>

                    @can('create', App\Models\Specialty::class)
                        @php($addingNew = old('specialty_form') === 'new' && $specialtyErrors->any())
                        <form method="POST" action="{{ route('admin.specialties.store') }}" class="flex flex-wrap items-start gap-3">
                            @csrf
                            <input type="hidden" name="specialty_form" value="new">
                            <div class="min-w-48 flex-1">
                                <label for="new-specialty" class="sr-only">Nouvelle spécialité</label>
                                <input id="new-specialty" name="name" type="text" required maxlength="100" placeholder="Nouvelle spécialité"
                                       value="{{ $addingNew ? old('name') : '' }}"
                                       @class(['field mt-0 h-11', 'field-error' => $addingNew && $specialtyErrors->has('name')])>
                                @if ($addingNew)
                                    <x-input-error class="mt-2" :messages="$specialtyErrors->get('name')" />
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        </form>
                    @endcan
                </div>
            </section>

            <section class="card p-5">
                <h2 class="card-title">{{ $activityTitle }}</h2>
                <dl class="mt-4 flex flex-wrap gap-x-10 gap-y-4">
                    <div class="flex flex-col-reverse">
                        <dt class="stat-label">rendez-vous</dt>
                        <dd class="stat-value">{{ $stats['appointments'] }}</dd>
                    </div>
                    <div class="flex flex-col-reverse">
                        <dt class="stat-label">d'absences ({{ $stats['no_shows'] }})</dt>
                        <dd class="stat-value">{{ number_format($stats['no_show_rate'], 1, ',', ' ') }} %</dd>
                    </div>
                    <div class="flex flex-col-reverse">
                        <dt class="stat-label">annulations</dt>
                        <dd class="stat-value">{{ $stats['cancellations'] }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-muted">
                    Taux d'absence : patients absents parmi les rendez-vous qui ont eu lieu ({{ $stats['completed'] }} terminés, {{ $stats['no_shows'] }} absents).
                </p>
            </section>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="card">
            @csrf
            @method('PUT')

            <div class="card-header"><h2 class="card-title">Paramètres du cabinet</h2></div>

            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="cabinet_name" value="Nom du cabinet" required />
                        <x-text-input id="cabinet_name" name="cabinet_name" type="text" :value="old('cabinet_name', $settings->cabinet_name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('cabinet_name')" />
                    </div>
                    <div>
                        <x-input-label for="address" value="Adresse" />
                        <x-text-input id="address" name="address" type="text" :value="old('address', $settings->address)" />
                        <x-input-error class="mt-2" :messages="$errors->get('address')" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $settings->phone)" placeholder="75 600 600" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <div class="grid gap-5 border-t border-line pt-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="opening_time" value="Ouverture (lun.–ven.)" required />
                        <x-text-input id="opening_time" name="opening_time" type="time" :value="old('opening_time', $settings->time('opening_time'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('opening_time')" />
                    </div>
                    <div>
                        <x-input-label for="closing_time" value="Fermeture (lun.–ven.)" required />
                        <x-text-input id="closing_time" name="closing_time" type="time" :value="old('closing_time', $settings->time('closing_time'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('closing_time')" />
                    </div>

                    <fieldset>
                        <legend class="label">Samedi</legend>
                        <div class="mt-1 flex items-center gap-2">
                            <label for="saturday_opening_time" class="sr-only">Ouverture le samedi</label>
                            <x-text-input id="saturday_opening_time" name="saturday_opening_time" type="time" class="mt-0 min-w-0 flex-1"
                                          :value="old('saturday_opening_time', $settings->time('saturday_opening_time'))" />
                            <span class="text-muted" aria-hidden="true">–</span>
                            <label for="saturday_closing_time" class="sr-only">Fermeture le samedi</label>
                            <x-text-input id="saturday_closing_time" name="saturday_closing_time" type="time" class="mt-0 min-w-0 flex-1"
                                          :value="old('saturday_closing_time', $settings->time('saturday_closing_time'))" />
                        </div>
                        <p class="mt-1 text-xs text-muted">Laisser vide si le cabinet est fermé le samedi.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('saturday_opening_time')" />
                        <x-input-error class="mt-2" :messages="$errors->get('saturday_closing_time')" />
                    </fieldset>

                    <div>
                        <x-input-label for="default_slot_minutes" value="Durée d'un créneau" required />
                        <select id="default_slot_minutes" name="default_slot_minutes" @class(['field', 'field-error' => $errors->has('default_slot_minutes')])>
                            @foreach ($slotDurations as $minutes)
                                <option value="{{ $minutes }}" @selected((int) old('default_slot_minutes', $settings->default_slot_minutes) === $minutes)>{{ $minutes }} min</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('default_slot_minutes')" />
                    </div>

                    <div>
                        <x-input-label for="cancellation_hours" value="Annulation par le patient" required />
                        <select id="cancellation_hours" name="cancellation_hours" @class(['field', 'field-error' => $errors->has('cancellation_hours')])>
                            @foreach ($cancellationHours as $hours)
                                <option value="{{ $hours }}" @selected((int) old('cancellation_hours', $settings->cancellation_hours) === $hours)>Jusqu'à {{ $hours }} h avant</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('cancellation_hours')" />
                    </div>

                    <div>
                        <x-input-label for="booking_days_ahead" value="Réservation au plus tard" required />
                        <select id="booking_days_ahead" name="booking_days_ahead" @class(['field', 'field-error' => $errors->has('booking_days_ahead')])>
                            @foreach ($bookingDays as $days)
                                <option value="{{ $days }}" @selected((int) old('booking_days_ahead', $settings->booking_days_ahead) === $days)>{{ $days }} jours à l'avance</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('booking_days_ahead')" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="reminder_hours" value="Rappel par e-mail" required />
                        <select id="reminder_hours" name="reminder_hours" @class(['field', 'field-error' => $errors->has('reminder_hours')])>
                            @foreach ($reminderHours as $hours)
                                <option value="{{ $hours }}" @selected((int) old('reminder_hours', $settings->reminder_hours) === $hours)>{{ $hours }} h avant le rendez-vous</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('reminder_hours')" />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-line px-5 py-4 sm:px-6">
                <a href="{{ route('admin.settings') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</x-app-layout>
