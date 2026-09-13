<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentReviewController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReversalController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Livewire\Students\StudentTable;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/configuracion/apariencia', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('/configuracion/apariencia', [BrandingController::class, 'update'])->name('branding.update');
    Route::get('/estudiantes', StudentTable::class)->name('students.index');
    Route::resource('students', StudentController::class)->only(['create', 'store', 'show', 'edit', 'update']);
    Route::resource('enrollments', EnrollmentController::class)->only(['index', 'create', 'store']);
    Route::get('/datos-por-revisar', [EnrollmentReviewController::class, 'index'])->name('enrollment-reviews.index');
    Route::get('/datos-por-revisar/{enrollment}/edit', [EnrollmentReviewController::class, 'edit'])->name('enrollment-reviews.edit');
    Route::put('/datos-por-revisar/{enrollment}', [EnrollmentReviewController::class, 'update'])->name('enrollment-reviews.update');
    Route::get('/cartera', PortfolioController::class)->name('portfolio.index');
    Route::get('/informes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/informes/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/enrollments/{enrollment}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/enrollments/{enrollment}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{payment}/reverse', [PaymentReversalController::class, 'store'])->name('payments.reverse');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::post('/imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
