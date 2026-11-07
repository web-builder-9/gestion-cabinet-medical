@include('errors.layout', [
    'code' => 503,
    'title' => 'Maintenance en cours',
    'message' => 'L\'application est en cours de mise à jour. Elle sera de nouveau disponible dans quelques minutes.',
    'icon' => 'settings',
    'tone' => 'neutral',
])
