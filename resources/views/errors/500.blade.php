{{-- BNF-07 : aucune information technique n'est affichée. --}}
@include('errors.layout', [
    'code' => 500,
    'title' => 'Erreur du serveur',
    'message' => 'Une erreur inattendue s\'est produite. Réessayez dans quelques instants ; si elle persiste, prévenez l\'administrateur du cabinet.',
    'icon' => 'warning',
    'tone' => 'danger',
])
