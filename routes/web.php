<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('landing', ['types' => \App\Models\DocumentType::query()->where('is_active', true)->get()]))->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/pengajuan', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::middleware('role:pengaju')->group(function (): void {
        Route::get('/pengajuan/buat', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/pengajuan', [SubmissionController::class, 'store'])->middleware('throttle:20,1')->name('submissions.store');
        Route::get('/pengajuan/{submission}/ubah', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('/pengajuan/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::post('/pengajuan/{submission}/kirim', [SubmissionController::class, 'submit'])->name('submissions.submit');
    });
    Route::get('/pengajuan/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/pengajuan/{submission}/lampiran/{attachment}', [SubmissionController::class, 'attachment'])->name('submissions.attachment');
    Route::middleware('role:verifikator')->prefix('/verifikasi')->name('reviews.')->group(function (): void {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/{submission}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{submission}/{action}', [ReviewController::class, 'decide'])->name('decide');
    });
    Route::get('/arsip', [ArchiveController::class, 'index'])->name('archive.index');
    Route::get('/arsip/{submission}/unduh', [ArchiveController::class, 'download'])->name('archive.download');
    Route::get('/dampak', [ImpactController::class, 'index'])->name('impact.index');
    Route::get('/metodologi', [ImpactController::class, 'methodology'])->name('impact.methodology');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware('role:admin')->prefix('/admin')->name('admin.')->group(function (): void {
        Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
        Route::post('/pengguna', [AdminController::class, 'saveUser'])->name('users.store');
        Route::put('/pengguna/{user}', [AdminController::class, 'saveUser'])->name('users.update');
        Route::post('/pengguna/{user}/status', [AdminController::class, 'toggleUser'])->name('users.toggle');
        Route::get('/layanan', [AdminController::class, 'services'])->name('services');
        Route::put('/layanan/{type}', [AdminController::class, 'saveService'])->name('services.update');
        Route::get('/faktor-emisi', [AdminController::class, 'factors'])->name('factors');
        Route::put('/faktor-emisi/{factor}', [AdminController::class, 'saveFactor'])->name('factors.update');
    });
    Route::get('/layanan-tersedia', fn () => response()->json(\App\Models\DocumentType::query()->where('is_active', true)->get(['code', 'name', 'description'])))->name('document-types.index');
});
