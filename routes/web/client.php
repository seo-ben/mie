<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Client\ClientDashboardController;
use App\Http\Controllers\Web\Client\ClientProfileController;
use App\Http\Controllers\Web\Client\ClientAccountController;
use App\Http\Controllers\Web\Client\ClientTransactionController;
use App\Http\Controllers\Web\Client\ClientLoanController;
use App\Http\Controllers\Web\Client\ClientTontineController;
use App\Http\Controllers\Web\Client\ClientNotificationController;

Route::middleware(['role:client'])->prefix('client')->name('client.')->group(function () {

    // Dashboard
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');

    // Profil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ClientProfileController::class, 'show'])->name('show');
        Route::get('edit', [ClientProfileController::class, 'edit'])->name('edit');
        Route::put('update', [ClientProfileController::class, 'update'])->name('update');
        Route::post('upload-document', [ClientProfileController::class, 'uploadDocument'])->name('upload-document');
        Route::delete('document/{id}', [ClientProfileController::class, 'deleteDocument'])->name('delete-document');
        Route::get('kyc-status', [ClientProfileController::class, 'kycStatus'])->name('kyc-status');
    });

    // Comptes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [ClientAccountController::class, 'index'])->name('index');
        Route::get('create', [ClientAccountController::class, 'create'])->name('create');
        Route::post('store', [ClientAccountController::class, 'store'])->name('store');
        Route::get('{account}', [ClientAccountController::class, 'show'])->name('show');
        Route::get('{account}/history', [ClientAccountController::class, 'history'])->name('history');
        Route::post('{account}/activate', [ClientAccountController::class, 'activate'])->name('activate');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [ClientTransactionController::class, 'index'])->name('index');
        Route::get('create', [ClientTransactionController::class, 'create'])->name('create');
        Route::post('deposit', [ClientTransactionController::class, 'deposit'])->name('deposit');
        Route::post('withdrawal', [ClientTransactionController::class, 'withdrawal'])->name('withdrawal');
        Route::get('{transaction}', [ClientTransactionController::class, 'show'])->name('show');
        Route::get('{transaction}/receipt', [ClientTransactionController::class, 'receipt'])->name('receipt');
        Route::get('export/pdf', [ClientTransactionController::class, 'exportPdf'])->name('export-pdf');
        Route::get('export/excel', [ClientTransactionController::class, 'exportExcel'])->name('export-excel');
    });

    // Prêts
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [ClientLoanController::class, 'index'])->name('index');
        Route::get('create', [ClientLoanController::class, 'create'])->name('create');
        Route::post('store', [ClientLoanController::class, 'store'])->name('store');
        Route::post('simulate', [ClientLoanController::class, 'simulate'])->name('simulate');
        Route::get('{loan}', [ClientLoanController::class, 'show'])->name('show');
        Route::get('{loan}/schedule', [ClientLoanController::class, 'schedule'])->name('schedule');
        Route::post('{loan}/payment', [ClientLoanController::class, 'payment'])->name('payment');
        Route::get('eligibility', [ClientLoanController::class, 'eligibility'])->name('eligibility');
    });

    // Tontines
    Route::prefix('tontines')->name('tontines.')->group(function () {
        Route::get('/', [ClientTontineController::class, 'index'])->name('index');
        Route::get('{tontine}', [ClientTontineController::class, 'show'])->name('show');
        Route::get('{tontine}/cycles', [ClientTontineController::class, 'cycles'])->name('cycles');
        Route::post('{tontine}/payment', [ClientTontineController::class, 'payment'])->name('payment');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [ClientNotificationController::class, 'index'])->name('index');
        Route::put('{notification}/read', [ClientNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::put('mark-all-read', [ClientNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });
});
