@include('errors.layout', [
    'code' => 404,
    'title' => 'Page introuvable',
    'message' => 'Cette page n\'existe pas ou a été déplacée. Vérifiez l\'adresse, ou repartez de l\'accueil.',
    'icon' => 'search',
    'tone' => 'neutral',
])
