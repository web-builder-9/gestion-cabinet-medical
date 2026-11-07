<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page publique (visiteur) : barre du haut avec « Nos médecins »,
 * « Connexion » et « Créer un compte » (maquettes 1 à 3).
 *   <x-guest-layout title="Connexion" width="md">…</x-guest-layout>
 * width : « md » pour un formulaire centré, « xl » pour un formulaire large,
 * « full » pour une page libre (accueil).
 */
class GuestLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public string $width = 'md',
    ) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
