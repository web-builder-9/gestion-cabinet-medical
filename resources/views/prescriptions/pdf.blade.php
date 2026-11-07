{{-- Ordonnance au format PDF A5 (dompdf, BF-22). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ordonnance {{ $consultation->prescriptionNumber() }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { margin: 0; }
    </style>
    @include('prescriptions.partials.styles')
</head>
<body>
    @include('prescriptions.partials.sheet')
</body>
</html>
