{{--
    « Mes rendez-vous » du patient (maquette 6) : à venir (modifier ou annuler jusqu'au délai, RG-16),
    historique, dernier compte rendu en lecture seule (BF-10, BF-11, RG-21).
    Variables : $patient, $upcoming, $history, $lastConsultation.
--}}
@php($settings = App\Models\Setting::current())

<x-app-layout :title="'Bonjour '.Auth::user()->first_name" subtitle="Vos rendez-vous et les comptes rendus de vos consultations.">
    <x-slot name="actions">
        @if ($patient)
            <a href="{{ route('booking.create') }}" class="btn btn-primary"><x-icon name="plus" /> Prendre un rendez-vous</a>
        @endif
    </x-slot>

    <x-flash-status />

    @if (! $patient)
        <div class="card p-6 text-sm text-ink-3">
            Votre compte n'est pas encore relié à un dossier patient. Contactez le secrétariat
            @if ($settings->phone) au {{ $settings->phone }} @endif.
        </div>
    @else
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <div class="flex flex-col gap-5">
                <section class="card">
                    <div class="card-header"><h2 class="card-title">À venir</h2></div>
                    <ul class="divide-y divide-line-soft">
                        @forelse ($upcoming as $appointment)
                            <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                                <div class="flex w-14 flex-none flex-col items-center rounded-lg bg-brand-soft py-1.5 leading-tight text-brand">
                                    <span class="text-xs font-semibold uppercase">{{ rtrim($appointment->starts_at->translatedFormat('D'), '.') }}</span>
                                    <span class="text-xl font-bold">{{ $appointment->starts_at->format('j') }}</span>
                                    <span class="text-xs font-semibold uppercase">{{ rtrim($appointment->starts_at->translatedFormat('M'), '.') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-ink">
                                        {{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at->format('H:i') }} · {{ $appointment->doctor->display_name }}
                                    </p>
                                    <p class="text-sm text-muted">{{ $appointment->reason ?? 'Sans motif' }}</p>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <x-appointment-status :status="$appointment->status" />
                                    @can('update', $appointment)
                                        <a href="{{ route('booking.edit', $appointment) }}" class="btn btn-secondary btn-sm">Modifier</a>
                                    @endcan
                                    @can('cancel', $appointment)
                                        <form method="POST" action="{{ route('booking.cancel', $appointment) }}"
                                              onsubmit="return confirm('Annuler ce rendez-vous ? Le créneau sera proposé à d\'autres patients.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-danger btn-sm">Annuler</button>
                                        </form>
                                    @else
                                        {{-- RG-16 : passé le délai, seul le secrétariat peut le faire. --}}
                                        <p class="w-full text-right text-xs text-muted">
                                            Moins de {{ $settings->cancellation_hours }} h avant : pour modifier ou annuler, appelez le cabinet{{ $settings->phone ? ' au '.$settings->phone : '' }}.
                                        </p>
                                    @endcan
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-muted">
                                Aucun rendez-vous à venir. <a href="{{ route('booking.create') }}" class="link">Prendre un rendez-vous</a>
                            </li>
                        @endforelse
                    </ul>
                </section>

                <section class="card">
                    <div class="card-header"><h2 class="card-title">Historique</h2></div>
                    <ul class="divide-y divide-line-soft">
                        @forelse ($history as $appointment)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <p class="font-semibold text-ink">
                                        {{ $appointment->starts_at->translatedFormat('j F Y') }} · {{ $appointment->starts_at->format('H:i') }} · {{ $appointment->doctor->display_name }}
                                    </p>
                                    <p class="text-sm text-muted">{{ $appointment->reason ?? 'Sans motif' }}</p>
                                </div>
                                <x-appointment-status :status="$appointment->status" />
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-muted">Aucun rendez-vous passé.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Dernier compte rendu</h2>
                    <span class="text-xs text-muted">Lecture seule</span>
                </div>
                @if ($lastConsultation)
                    <div class="space-y-3 p-5 text-sm">
                        <p class="font-semibold text-ink">
                            {{ $lastConsultation->consulted_at->translatedFormat('j F Y') }} · {{ $lastConsultation->appointment->doctor->display_name }}
                        </p>
                        <p><span class="text-muted">Motif :</span> {{ $lastConsultation->reason ?? '—' }}</p>
                        <p><span class="text-muted">Diagnostic :</span> {{ $lastConsultation->diagnosis ?? '—' }}</p>
                        <div>
                            <span class="text-muted">Traitement :</span>
                            @foreach ($lastConsultation->prescriptionItems as $item)
                                <p>{{ $item->medication }} {{ $item->dosage }} <span class="text-muted">— {{ $item->instructions() }}</span></p>
                            @endforeach
                            {{ $lastConsultation->treatment ?? ($lastConsultation->prescriptionItems->isEmpty() ? '—' : '') }}
                        </div>
                        @if ($lastConsultation->prescriptionItems->isNotEmpty())
                            <a href="{{ route('consultations.prescription.pdf', $lastConsultation) }}" class="btn btn-secondary w-full">
                                <x-icon name="download" /> Télécharger l'ordonnance (PDF)
                            </a>
                        @endif
                        <a href="{{ route('my-consultations.index') }}" class="link inline-block">Voir tous mes comptes rendus</a>
                    </div>
                @else
                    <p class="p-5 text-sm text-muted">Vos comptes rendus apparaîtront ici après vos consultations.</p>
                @endif
            </section>
        </div>
    @endif
</x-app-layout>
