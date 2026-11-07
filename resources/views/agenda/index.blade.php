{{--
    Agenda par jour ou par semaine (maquette 11, BF-15 et BF-19).
    Variables : $doctor, $doctors, $view ('jour' | 'semaine'), $date, $days, $agenda (WeekAgenda::for), $previous, $next.
--}}
@php
    $isDoctor = Auth::user()->isDoctor();
    $title = $isDoctor ? 'Mon agenda' : 'Agenda de '.$doctor->display_name;
    $period = $view === 'jour'
        ? ucfirst($date->translatedFormat('l j F Y'))
        : 'Semaine du '.$days[0]->translatedFormat('j').' au '.end($days)->translatedFormat('j F Y');
    $link = fn (array $params) => route('agenda', [...['vue' => $view, 'date' => $date->format('Y-m-d'), 'medecin' => $isDoctor ? null : $doctor->id], ...$params]);
@endphp

<x-app-layout :title="$title" :subtitle="$period.' · '.trans_choice(':count rendez-vous|:count rendez-vous', $agenda['count'])">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $link(['date' => $previous->format('Y-m-d')]) }}" class="btn btn-secondary px-3" aria-label="{{ $view === 'jour' ? 'Jour précédent' : 'Semaine précédente' }}"><x-icon name="chevron-left" /></a>
            <a href="{{ $link(['date' => $next->format('Y-m-d')]) }}" class="btn btn-secondary px-3" aria-label="{{ $view === 'jour' ? 'Jour suivant' : 'Semaine suivante' }}"><x-icon name="chevron-right" /></a>
            <a href="{{ $link(['date' => today()->format('Y-m-d')]) }}" class="btn btn-secondary">Aujourd'hui</a>
            <nav class="segmented" aria-label="Affichage">
                <a href="{{ $link(['vue' => 'jour']) }}" @if ($view === 'jour') aria-current="page" @endif>Jour</a>
                <a href="{{ $link(['vue' => 'semaine']) }}" @if ($view === 'semaine') aria-current="page" @endif>Semaine</a>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($isDoctor)
                <a href="{{ route('availabilities.index') }}" class="btn btn-secondary">Gérer mes disponibilités</a>
            @else
                <form method="GET" action="{{ route('agenda') }}">
                    <input type="hidden" name="vue" value="{{ $view }}">
                    <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                    <label for="medecin" class="sr-only">Médecin</label>
                    <select id="medecin" name="medecin" class="field mt-0 h-11 w-auto" onchange="this.form.submit()">
                        @foreach ($doctors as $option)
                            <option value="{{ $option->id }}" @selected($option->is($doctor))>{{ $option->display_name }} · {{ $option->specialty->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-secondary">Afficher</button></noscript>
                </form>
                @can('create', App\Models\Appointment::class)
                    <a href="{{ route('appointments.create', ['medecin' => $doctor->id, 'date' => $date->format('Y-m-d')]) }}" class="btn btn-primary">
                        <x-icon name="plus" /> Nouveau rendez-vous
                    </a>
                @endcan
            @endif
        </div>
    </div>

    <div class="card overflow-x-auto">
        <div @class(['grid', 'min-w-[760px]' => $view === 'semaine'])
             style="grid-template-columns: 4rem repeat({{ count($days) }}, minmax(0, 1fr))">
            {{-- En-têtes des jours --}}
            <div class="border-b border-line"></div>
            @foreach ($agenda['columns'] as $column)
                @php($isToday = $column['date']->isToday())
                <div class="flex flex-col items-center gap-0.5 border-b border-l border-line py-2">
                    <span class="text-xs text-muted">{{ ucfirst(rtrim($column['date']->translatedFormat('D'), '.')) }}</span>
                    <span @class([
                        'inline-flex size-8 items-center justify-center rounded-full text-lg font-bold',
                        'bg-brand text-white' => $isToday,
                        'text-ink' => ! $isToday,
                    ])>{{ $column['date']->format('j') }}</span>
                </div>
            @endforeach

            {{-- Heures --}}
            <div class="relative" style="height: {{ $agenda['height'] }}px">
                @foreach ($agenda['hours'] as $i => $hour)
                    <span class="absolute right-2 -translate-y-1/2 text-xs text-muted tabular-nums"
                          style="top: {{ $i * 60 * App\Services\WeekAgenda::PX_PER_MINUTE }}px">{{ $hour }}</span>
                @endforeach
            </div>

            {{-- Colonnes : lignes des heures, périodes grisées, rendez-vous --}}
            @foreach ($agenda['columns'] as $column)
                <div @class(['relative border-l border-line', 'bg-subtle' => $column['date']->isToday()]) style="height: {{ $agenda['height'] }}px">
                    @foreach ($agenda['hours'] as $i => $hour)
                        <div class="absolute inset-x-0 border-t border-line-soft" style="top: {{ $i * 60 * App\Services\WeekAgenda::PX_PER_MINUTE }}px"></div>
                    @endforeach

                    @foreach ($column['shaded'] as $period)
                        <div class="hatched absolute inset-x-0 flex items-center justify-center px-2 text-center text-xs text-muted"
                             style="top: {{ $period['top'] }}px; height: {{ $period['height'] }}px">
                            @if ($period['height'] >= 40)
                                {{ $period['label'] }}
                            @endif
                        </div>
                    @endforeach

                    @foreach ($column['blocks'] as $block)
                        <x-agenda-block :appointment="$block['appointment']" :top="$block['top']" :height="$block['height']" />
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-ink-3" aria-label="Légende">
        <li class="flex items-center gap-2"><span class="size-3 rounded-sm border border-wait-ink/30 bg-wait"></span> En attente</li>
        <li class="flex items-center gap-2"><span class="size-3 rounded-sm border border-ok-ink/25 bg-ok"></span> Confirmé</li>
        <li class="flex items-center gap-2"><span class="size-3 rounded-sm border border-done-ink/25 bg-done"></span> Terminé</li>
        <li class="flex items-center gap-2"><span class="size-3 rounded-sm border border-danger-ink/25 bg-danger"></span> Annulé (créneau libéré)</li>
        <li class="flex items-center gap-2"><span class="size-3 rounded-sm border border-line-strong bg-neutral"></span> Absent</li>
    </ul>
</x-app-layout>
