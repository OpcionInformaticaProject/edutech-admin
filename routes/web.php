<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReversalController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ReceiptController;
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
    Route::get('/estudiantes', StudentTable::class)->name('students.index');
    Route::resource('students', StudentController::class)->only(['create', 'store', 'show', 'edit', 'update']);
    Route::resource('enrollments', EnrollmentController::class)->only(['index', 'create', 'store']);
    Route::get('/cartera', PortfolioController::class)->name('portfolio.index');
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
