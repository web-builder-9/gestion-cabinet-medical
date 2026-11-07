{{--
    Prise de rendez-vous, étape 2 : choisir le créneau (maquette 5, BF-07, BF-08) ;
    sert aussi à modifier un rendez-vous (BF-09).
    Les créneaux sont des boutons radio rattachés au formulaire de droite (attribut « form ») ;
    Alpine.js affiche le créneau choisi dans « Votre demande ».
    Variables : voir Patient\BookingController::slotData().
--}}
@php
    $moving = $appointment !== null;
    $selected = old('time', $moving && $appointment->starts_at->isSameDay($day) ? $appointment->starts_at->format('H:i') : null);
    $ends = collect([...$morning, ...$afternoon])->mapWithKeys(fn ($s) => [$s['slot']->label() => $s['slot']->end->format('H:i')]);
    $dayUrl = fn ($date) => $moving
        ? route('booking.edit', ['appointment' => $appointment, 'jour' => $date->format('Y-m-d')])
        : route('booking.create', ['medecin' => $doctor->id, 'jour' => $date->format('Y-m-d')]);
@endphp

<x-app-layout :title="$moving ? 'Modifier mon rendez-vous' : 'Prendre un rendez-vous'">
    <x-slot name="actions">
        @include('booking.partials.steps', ['current' => 2])
    </x-slot>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]"
         x-data="{ time: @js($selected), ends: @js($ends) }">
        <section class="card">
            <div class="flex flex-wrap items-center gap-4 border-b border-line p-5">
                <span class="avatar size-12 text-base">{{ $doctor->user->initials }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ $doctor->display_name }}</p>
                    <p class="text-sm text-muted">{{ $doctor->specialty->name }}@if ($slotMinutes) · créneaux de {{ $slotMinutes }} min @endif</p>
                </div>
                @unless ($moving)
                    <a href="{{ route('booking.create') }}" class="link text-sm">Changer de médecin</a>
                @endunless
            </div>

            <div class="space-y-6 p-5">
                {{-- Jours de la semaine --}}
                <nav class="flex items-center gap-2 overflow-x-auto" aria-label="Jours">
                    @if ($previousWeek)
                        <a href="{{ $dayUrl($previousWeek->max($today)) }}" class="btn btn-secondary px-2" aria-label="Semaine précédente"><x-icon name="chevron-left" /></a>
                    @endif
                    @foreach ($week as $date)
                        @php($open = $date->gte($today) && $date->lte($horizon))
                        @php($current = $date->isSameDay($day))
                        @if ($open)
                            <a href="{{ $dayUrl($date) }}" @class([
                                'flex w-16 flex-none flex-col items-center rounded-lg border py-2 text-sm',
                                'border-brand bg-brand text-white' => $current,
                                'border-line-strong bg-white text-ink-2 hover:border-brand' => ! $current,
                            ]) @if ($current) aria-current="date" @endif>
                                <span>{{ ucfirst(rtrim($date->translatedFormat('D'), '.')) }}</span>
                                <span class="text-lg font-bold">{{ $date->format('j') }}</span>
                            </a>
                        @else
                            <span class="flex w-16 flex-none flex-col items-center rounded-lg border border-line bg-subtle py-2 text-sm text-line-strong">
                                <span>{{ ucfirst(rtrim($date->translatedFormat('D'), '.')) }}</span>
                                <span class="text-lg font-bold">{{ $date->format('j') }}</span>
                            </span>
                        @endif
                    @endforeach
                    @if ($nextWeek)
                        <a href="{{ $dayUrl($nextWeek) }}" class="btn btn-secondary px-2" aria-label="Semaine suivante"><x-icon name="chevron-right" /></a>
                    @endif
                </nav>

                @foreach (['Matin' => $morning, 'Après-midi' => $afternoon] as $label => $slots)
                    @if ($slots->isNotEmpty())
                        <fieldset>
                            <legend class="mb-3 font-semibold text-ink">{{ $label }}</legend>
                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                                @foreach ($slots as ['slot' => $slot, 'bookable' => $bookable])
                                    @if ($bookable)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="time" value="{{ $slot->label() }}" form="booking-form" x-model="time" class="peer sr-only"
                                                   @checked($selected === $slot->label())>
                                            <span class="flex h-11 items-center justify-center rounded-lg border border-brand font-semibold text-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">
                                                {{ $slot->label() }}
                                            </span>
                                        </label>
                                    @else
                                        <span class="flex h-11 items-center justify-center rounded-lg border border-line bg-subtle text-line-strong line-through" title="Indisponible">
                                            {{ $slot->label() }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                @endforeach

                @if ($morning->isEmpty() && $afternoon->isEmpty())
                    <p class="rounded-lg bg-subtle px-4 py-6 text-center text-sm text-muted">Pas de consultation ce jour-là : choisissez un autre jour.</p>
                @else
                    <p class="text-sm text-muted">Les créneaux barrés sont déjà réservés ou hors des disponibilités du médecin.</p>
                @endif
            </div>
        </section>

        <form id="booking-form" method="POST" action="{{ $moving ? route('booking.update', $appointment) : route('booking.store') }}" class="card space-y-5 p-5">
            @csrf
            @if ($moving)
                @method('PUT')
            @else
                <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
            @endif
            <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">

            <h2 class="card-title">Votre demande</h2>

            <div class="rounded-lg bg-subtle p-4">
                <p class="font-semibold text-ink">{{ ucfirst($day->translatedFormat('l j F Y')) }}</p>
                <p class="text-ink-3" x-show="time" x-cloak><span x-text="time + ' – ' + ends[time]"></span> · {{ $doctor->display_name }}</p>
                <p class="text-muted" x-show="! time">Choisissez un créneau.</p>
            </div>
            <x-input-error :messages="$errors->get('time')" />
            <x-input-error :messages="$errors->get('date')" />

            <div>
                <x-input-label for="reason" value="Motif (facultatif)" />
                <x-text-input id="reason" name="reason" type="text" maxlength="255" placeholder="Ex. : Contrôle après bronchite"
                              :value="old('reason', $appointment?->reason)" />
                <x-input-error class="mt-2" :messages="$errors->get('reason')" />
            </div>

            <div class="alert alert-warning">
                <x-icon name="clock" />
                <span>
                    Votre rendez-vous sera « En attente » jusqu'à sa confirmation par le secrétariat.
                    Vous recevrez un e-mail, puis un rappel {{ $settings->reminder_hours }} h avant.
                </span>
            </div>

            <button type="submit" class="btn btn-primary w-full" x-bind:disabled="! time">
                {{ $moving ? 'Enregistrer la modification' : 'Envoyer la demande' }}
            </button>

            <p class="text-sm text-muted">Modification ou annulation en ligne possible jusqu'à {{ $settings->cancellation_hours }} h avant.</p>
        </form>
    </div>
</x-app-layout>
