<?php

use App\Http\Controllers\ProfileController;
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

require __DIR__.'/auth.php';
