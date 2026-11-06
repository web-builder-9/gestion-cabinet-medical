<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Application interne : pas de page vitrine. L'accueil mène au tableau de bord,
// qui renvoie lui-même vers la connexion si l'utilisateur n'est pas connecté.
Route::redirect('/', '/dashboard');

// Tableau de bord : chaque rôle a le sien (voir DashboardController).
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Patients : personnel du cabinet (middleware) ; chaque action vérifie en plus
// la PatientPolicy (ex. : suppression réservée au médecin).
Route::middleware(['auth', 'role:doctor,secretary'])->group(function () {
    // Réservé au médecin (en plus des Policies). Déclaré AVANT la ressource :
    // sinon « /patients/archives » serait pris pour la fiche d'un patient.
    Route::middleware('role:doctor')->group(function () {
        Route::get('patients/archives', [PatientController::class, 'archived'])
            ->name('patients.archived');
        Route::patch('patients/{patient}/restore', [PatientController::class, 'restore'])
            ->withTrashed() // autorise un patient archivé dans l'URL
            ->name('patients.restore');

        Route::get('patients/{patient}/dossier/edit', [MedicalRecordController::class, 'edit'])
            ->name('patients.medical-record.edit');
        Route::put('patients/{patient}/dossier', [MedicalRecordController::class, 'update'])
            ->name('patients.medical-record.update');
    });

    Route::resource('patients', PatientController::class);
});

// Administration : administrateur uniquement (RG-03 ; middleware + Policies).
// URL en français, noms de routes en anglais (admin.users.*, admin.settings…).
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('utilisateurs', [UserController::class, 'index'])->name('users.index');
    Route::post('utilisateurs', [UserController::class, 'store'])->name('users.store');
    Route::get('utilisateurs/{user}/modifier', [UserController::class, 'edit'])->name('users.edit');
    Route::put('utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('utilisateurs/{user}/desactiver', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('utilisateurs/{user}/reactiver', [UserController::class, 'activate'])->name('users.activate');
    Route::post('utilisateurs/{user}/invitation', [UserController::class, 'invite'])->name('users.invite');
});

require __DIR__.'/auth.php';
