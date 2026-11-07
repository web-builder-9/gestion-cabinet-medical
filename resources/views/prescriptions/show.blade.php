{{--
    Aperçu de l'ordonnance avant impression ou téléchargement (maquette 15).
    À l'impression (Ctrl+P ou bouton « Imprimer »), seule la feuille A5 est imprimée.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('layouts.partials.head', ['pageTitle' => 'Ordonnance '.$consultation->prescriptionNumber()])
    @include('prescriptions.partials.styles')
    <style>
        @page { size: A5; margin: 14mm 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .paper { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: auto !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#e6ecec] font-sans antialiased">
    <header class="no-print border-b border-line bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ Auth::user()->isDoctor() ? route('consultations.show', $consultation) : route('dashboard') }}" class="link inline-flex items-center gap-1 font-semibold">
                <x-icon name="chevron-left" /> {{ Auth::user()->isDoctor() ? 'Retour à la consultation' : 'Mes rendez-vous' }}
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-muted">Format A5 · 1 page</span>
                <a href="{{ route('consultations.prescription.pdf', $consultation) }}" class="btn btn-secondary"><x-icon name="download" /> Télécharger le PDF</a>
                <button type="button" class="btn btn-primary" onclick="window.print()"><x-icon name="printer" /> Imprimer</button>
            </div>
        </div>
        <div class="mx-auto max-w-5xl px-4"><x-flash-status class="mb-3" /></div>
    </header>

    <main class="px-4 py-8">
        <div class="paper mx-auto w-full max-w-[148mm] bg-white p-[12mm] shadow-lg">
            @include('prescriptions.partials.sheet')
        </div>
    </main>
</body>
</html>
