<?php

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Doctor\AvailabilityController;
use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\UnavailabilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\Patient\BookingController;
use App\Http\Controllers\Patient\MyConsultationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Accueil public : les médecins et leur prochain créneau (maquette 1).
Route::get('/', HomeController::class)->name('home');

// Tableau de bord : chaque rôle a le sien (voir DashboardController).
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Patients et rendez-vous : médecin et secrétaire (middleware) ; chaque action vérifie
// en plus sa Policy (ex. : archivage réservé au médecin, RG-17 pour les rendez-vous).
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
    Route::post('patients/{patient}/invitation', [PatientController::class, 'invite'])->name('patients.invite');

    // Agenda (maquette 11) et rendez-vous (maquettes 7 et 10). « nouveau » est
    // déclaré avant {appointment}, sinon il serait pris pour un numéro de rendez-vous.
    Route::get('agenda', AgendaController::class)->name('agenda');
    Route::get('rendez-vous', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('rendez-vous/nouveau', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('rendez-vous', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('rendez-vous/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('rendez-vous/{appointment}/deplacer', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('rendez-vous/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('rendez-vous/{appointment}/confirmer', [AppointmentStatusController::class, 'confirm'])->name('appointments.confirm');
    Route::patch('rendez-vous/{appointment}/annuler', [AppointmentStatusController::class, 'cancel'])->name('appointments.cancel');
    Route::patch('rendez-vous/{appointment}/absent', [AppointmentStatusController::class, 'noShow'])->name('appointments.no-show');
});

// Espace patient : prise de rendez-vous en ligne, modification et annulation (RG-16).
// « Mes rendez-vous » est le tableau de bord du patient (/dashboard).
Route::middleware(['auth', 'role:patient'])->group(function () {
    Route::get('prendre-rendez-vous', [BookingController::class, 'create'])->name('booking.create');
    Route::post('prendre-rendez-vous', [BookingController::class, 'store'])->name('booking.store');
    Route::get('mes-rendez-vous/{appointment}/modifier', [BookingController::class, 'edit'])->name('booking.edit');
    Route::put('mes-rendez-vous/{appointment}', [BookingController::class, 'update'])->name('booking.update');
    Route::patch('mes-rendez-vous/{appointment}/annuler', [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::get('mes-comptes-rendus', [MyConsultationController::class, 'index'])->name('my-consultations.index');
});

// Espace médecin : disponibilités, indisponibilités et consultations ; chaque médecin gère les siennes
// (middleware + AvailabilityPolicy, UnavailabilityPolicy, ConsultationPolicy).
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('disponibilites', [AvailabilityController::class, 'index'])->name('availabilities.index');
    Route::post('disponibilites', [AvailabilityController::class, 'store'])->name('availabilities.store');
    Route::get('disponibilites/{availability}/modifier', [AvailabilityController::class, 'edit'])->name('availabilities.edit');
    Route::put('disponibilites/{availability}', [AvailabilityController::class, 'update'])->name('availabilities.update');
    Route::delete('disponibilites/{availability}', [AvailabilityController::class, 'destroy'])->name('availabilities.destroy');

    Route::post('indisponibilites', [UnavailabilityController::class, 'store'])->name('unavailabilities.store');
    Route::delete('indisponibilites/{unavailability}', [UnavailabilityController::class, 'destroy'])->name('unavailabilities.destroy');

    // Consultations (maquette 14) : ConsultationPolicy (RG-19 à RG-21).
    Route::get('consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('rendez-vous/{appointment}/consultation', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('rendez-vous/{appointment}/consultation', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
    Route::get('consultations/{consultation}/modifier', [ConsultationController::class, 'edit'])->name('consultations.edit');
    Route::put('consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
});

// Ordonnance (maquette 15) : médecins et patient concerné (ConsultationPolicy::view, RG-21).
Route::middleware(['auth', 'role:doctor,patient'])->group(function () {
    Route::get('consultations/{consultation}/ordonnance', [PrescriptionController::class, 'show'])->name('consultations.prescription');
    Route::get('consultations/{consultation}/ordonnance/pdf', [PrescriptionController::class, 'download'])->name('consultations.prescription.pdf');
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

    Route::get('parametres', [SettingController::class, 'edit'])->name('settings');
    Route::put('parametres', [SettingController::class, 'update'])->name('settings.update');

    Route::post('specialites', [SpecialtyController::class, 'store'])->name('specialties.store');
    Route::put('specialites/{specialty}', [SpecialtyController::class, 'update'])->name('specialties.update');
    Route::delete('specialites/{specialty}', [SpecialtyController::class, 'destroy'])->name('specialties.destroy');
});

require __DIR__.'/auth.php';
