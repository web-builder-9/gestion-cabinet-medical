@php
    // Message précis passé par abort(403, '…') ; sinon, message général.
    $detail = $exception?->getMessage();
    $default = $detail === '' || $detail === null || $detail === 'This action is unauthorized.';
@endphp

@include('errors.layout', [
    'code' => 403,
    'title' => 'Accès refusé',
    'message' => $default
        ? 'Votre rôle ne permet pas d\'ouvrir cette page. Si vous pensez que c\'est une erreur, contactez l\'administrateur du cabinet.'
        : $detail,
    'icon' => 'lock',
    'tone' => 'danger',
])
