<?php

use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

// Application interne : pas de page vitrine. L'accueil mène au tableau de bord,
// qui renvoie lui-même vers la connexion si l'utilisateur n'est pas connecté.
Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Patients : personnel du cabinet (middleware) ; chaque action vérifie en plus
// la PatientPolicy (ex. : suppression réservée au médecin).
Route::middleware(['auth', 'role:doctor,secretary'])->group(function () {
    Route::resource('patients', PatientController::class)->except(['destroy']);

    // Dossier médical : médecin uniquement (en plus de la MedicalRecordPolicy).
    Route::middleware('role:doctor')->group(function () {
        Route::get('patients/{patient}/dossier/edit', [MedicalRecordController::class, 'edit'])
            ->name('patients.medical-record.edit');
        Route::put('patients/{patient}/dossier', [MedicalRecordController::class, 'update'])
            ->name('patients.medical-record.update');
    });
});

// Gestion du personnel : médecin uniquement (middleware + UserPolicy).
// URL en français (/personnel), noms de routes en anglais (staff.*).
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::resource('personnel', StaffController::class)
        ->parameters(['personnel' => 'user'])
        ->names('staff')
        ->except(['show', 'destroy']);
});

require __DIR__.'/auth.php';
