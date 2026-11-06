<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page des pages connectées.
 *   <x-app-layout title="Patients" subtitle="248 patients">…</x-app-layout>
 * Le personnel a un menu latéral, le patient une barre en haut (maquettes).
 */
class AppLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
