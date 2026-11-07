{{--
    Prise (BF-13) ou déplacement (BF-14) d'un rendez-vous par le personnel (maquette 10).

    Trois formulaires dans la même carte :
      1. recherche du patient (GET), seulement lors d'une prise de rendez-vous ;
      2. choix du médecin et de la date (GET : la page se recharge avec les créneaux du jour) ;
      3. le rendez-vous lui-même (POST ou PUT) : créneau, motif, notes.
    Alpine.js garde le créneau choisi (« time ») : cliquer sur « Libre » à droite le sélectionne.

    Variables : voir AppointmentController::formData().
--}}
@php
    $moving = $appointment !== null;
    $pageUrl = $moving ? route('appointments.edit', $appointment) : route('appointments.create');
    $context = ['patient' => $patient?->id, 'medecin' => $doctor?->id, 'date' => $date->format('Y-m-d')];
    $requested = old('time');
@endphp

<x-app-layout :title="$moving ? 'Déplacer le rendez-vous' : 'Nouveau rendez-vous'"
              :subtitle="$moving ? 'Actuellement : '.App\Support\Dates::short($appointment->starts_at).' avec '.$appointment->doctor->display_name : null">
    <x-flash-status />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
         x-data="{ time: @js($requested ?? ($moving && $appointment->starts_at->isSameDay($date) ? $appointment->starts_at->format('H:i') : '')) }">
        <div class="card">
            {{-- 1. Patient --}}
            <section class="space-y-3 p-5 sm:p-6">
                <x-input-label value="Patient" required />

                @if ($patient)
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line-strong p-3">
                        <span class="avatar">{{ $patient->user->initials }}</span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="font-semibold text-ink">{{ $patient->full_name }}</p>
                            <p class="text-sm text-muted">{{ $patient->age }} ans · {{ $patient->user->phone }}</p>
                        </div>
                        @unless ($moving)
                            <a href="{{ route('appointments.create', [...$context, 'patient' => null]) }}" class="link text-sm">Changer</a>
                        @endunless
                    </div>
                @else
                    <form method="GET" action="{{ $pageUrl }}" class="flex flex-wrap gap-3" role="search">
                        <input type="hidden" name="medecin" value="{{ $doctor?->id }}">
                        <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                        <label for="q" class="sr-only">Rechercher un patient</label>
                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Nom, prénom, CIN ou téléphone" autofocus
                               @class(['field mt-0 h-11 min-w-56 flex-1', 'field-error' => $errors->has('patient_id')])>
                        <button type="submit" class="btn btn-secondary">Rechercher</button>
                    </form>
                    <x-input-error :messages="$errors->get('patient_id')" />

                    @if ($search !== '')
                        <ul class="divide-y divide-line-soft rounded-lg border border-line">
                            @forelse ($patientResults as $result)
                                <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                    <span><strong class="font-semibold">{{ $result->full_name }}</strong> <span class="text-muted">· {{ $result->age }} ans · {{ $result->user->phone }}</span></span>
                                    <a href="{{ route('appointments.create', [...$context, 'patient' => $result->id]) }}" class="btn btn-secondary btn-sm">Choisir</a>
                                </li>
                            @empty
                                <li class="px-3 py-3 text-sm text-muted">
                                    Aucun patient trouvé.
                                    <a href="{{ route('patients.create') }}" class="link">Créer son dossier</a>
                                </li>
                            @endforelse
                        </ul>
                    @endif
                @endif
            </section>

            {{-- 2. Médecin et date : la page se recharge pour afficher les créneaux du jour. --}}
            <form method="GET" action="{{ $pageUrl }}" class="grid gap-5 border-t border-line p-5 sm:grid-cols-2 sm:p-6">
                @if ($patient && ! $moving)
                    <input type="hidden" name="patient" value="{{ $patient->id }}">
                @endif
                <div>
                    <x-input-label for="medecin" value="Médecin" required />
                    <select id="medecin" name="medecin" class="field" onchange="this.form.submit()">
                        @foreach ($doctors as $option)
                            <option value="{{ $option->id }}" @selected($doctor?->is($option))>{{ $option->display_name }} · {{ $option->specialty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="date" value="Date" required />
                    <x-text-input id="date" name="date" type="date" :value="$date->format('Y-m-d')" onchange="this.form.submit()"
                                  :min="today()->format('Y-m-d')" :max="$horizon->format('Y-m-d')" />
                </div>
                <noscript><button type="submit" class="btn btn-secondary">Afficher les créneaux</button></noscript>
            </form>

            {{-- 3. Le rendez-vous --}}
            <form method="POST" action="{{ $moving ? route('appointments.update', $appointment) : route('appointments.store') }}">
                @csrf
                @if ($moving)
                    @method('PUT')
                @else
                    <input type="hidden" name="patient_id" value="{{ $patient?->id }}">
                @endif
                <input type="hidden" name="doctor_id" value="{{ $doctor?->id }}">
                <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">

                <div class="space-y-5 border-t border-line p-5 sm:p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="time" value="Créneau" required />
                            <select id="time" name="time" x-model="time" @class(['field', 'field-error' => $errors->has('time')]) @disabled($freeSlots->isEmpty())>
                                @if ($freeSlots->isEmpty())
                                    <option value="">Aucun créneau libre ce jour-là</option>
                                @else
                                    <option value="">Choisir…</option>
                                    @foreach ($freeSlots as $slot)
                                        <option value="{{ $slot->label() }}">{{ $slot->label() }} – {{ $slot->end->format('H:i') }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <x-input-label for="state" :value="$moving ? 'État' : 'État à la création'" />
                            {{-- RG-15 : pris par le secrétariat, le rendez-vous est confirmé d'emblée. --}}
                            <input id="state" type="text" class="field bg-subtle text-ink-3" disabled
                                   value="{{ $moving ? $appointment->status->label().' (inchangé)' : 'Confirmé (pris par le secrétariat)' }}">
                        </div>
                    </div>

                    @if ($errors->has('time'))
                        <div class="alert alert-danger flex-col sm:flex-row" role="alert">
                            <x-icon name="warning" />
                            <div class="space-y-3">
                                @foreach ($errors->get('time') as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                                @if ($suggestions->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold">Créneaux libres proches :</span>
                                        @foreach ($suggestions as $slot)
                                            <button type="button" class="rounded-full border border-brand bg-white px-3 py-1 text-sm font-semibold text-brand hover:bg-brand-soft"
                                                    x-on:click="time = @js($slot->label())">{{ $slot->label() }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('doctor_id')" />

                    <div>
                        <x-input-label for="reason" value="Motif" />
                        <x-text-input id="reason" name="reason" type="text" maxlength="255" placeholder="Ex. : Contrôle de tension"
                                      :value="old('reason', $appointment?->reason)" />
                        <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes pour le médecin" />
                        <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Informations utiles avant la consultation"
                                  @class(['field', 'field-error' => $errors->has('notes')])>{{ old('notes', $appointment?->notes) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-line px-5 py-4 sm:px-6">
                    <a href="{{ $moving ? route('appointments.show', $appointment) : ($patient ? route('patients.show', $patient) : route('appointments.index')) }}" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary" @disabled(! $patient || ! $doctor)>
                        {{ $moving ? 'Enregistrer le déplacement' : 'Enregistrer le rendez-vous' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Journée du médecin : créneaux pris et libres (cliquer sur « Libre » choisit le créneau). --}}
        <section class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">{{ ucfirst($date->translatedFormat('l j F')) }}</h2>
                @if ($doctor)
                    <span class="text-sm text-muted">Dr {{ $doctor->user->last_name }}</span>
                @endif
            </div>
            <ul class="divide-y divide-line-soft">
                @forelse ($daySlots as $slot)
                    @php($booked = $dayAppointments->get($slot->label()))
                    @php($isCurrent = $moving && $booked?->is($appointment))
                    <li @class(['flex items-center justify-between gap-3 px-5 py-2.5 text-sm', 'bg-danger/40' => $requested === $slot->label() && $errors->has('time')])
                        @if ($slot->isFree()) x-bind:class="time === @js($slot->label()) ? 'bg-brand-soft' : ''" @endif>
                        <span class="font-semibold tabular-nums">{{ $slot->label() }}</span>
                        @if ($slot->isFree() && $slot->start->lte($horizon))
                            <button type="button" class="font-semibold text-done-ink hover:underline" x-on:click="time = @js($slot->label())">
                                {{ $isCurrent ? 'Créneau actuel' : 'Libre' }}
                            </button>
                        @elseif ($booked)
                            <span class="text-ink-3">{{ $booked->patient->user->short_name }} · {{ mb_strtolower($booked->status->label()) }}</span>
                        @elseif ($slot->state === App\Services\Slot::UNAVAILABLE)
                            <span class="text-muted">Indisponible</span>
                        @else
                            <span class="text-muted">Passé</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Pas de consultation ce jour-là.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
