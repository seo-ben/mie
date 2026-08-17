<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Agent\AgentDashboardController;
use App\Http\Controllers\Web\Agent\AgentClientController;
use App\Http\Controllers\Web\Agent\AgentReportController;
use App\Http\Controllers\Web\Agent\AgentTransactionController;
use App\Http\Controllers\Web\Agent\AgentAccountController;

Route::middleware(['role:agent_terrain,agent_agence'])->prefix('agent')->name('agent.')->group(function () {

    Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/daily-stats', [AgentDashboardController::class, 'dailyStats'])->name('daily-stats');
    Route::get('/daily-collection', [AgentAccountController::class, 'dailyCollection'])->name('accounts.daily-collection');
    // Gestion des clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [AgentClientController::class, 'index'])->name('index');
        Route::get('create', [AgentClientController::class, 'create'])->name('create');
        Route::post('store', [AgentClientController::class, 'store'])->name('store');
        Route::get('{client}', [AgentClientController::class, 'show'])->name('show');
        Route::get('{client}/edit', [AgentClientController::class, 'edit'])->name('edit');
        Route::put('{client}', [AgentClientController::class, 'update'])->name('update');
        Route::get('search', [AgentClientController::class, 'search'])->name('search');
        Route::post('{client}/activate-accounts', [AgentClientController::class, 'activateAccounts'])->name('activate-accounts');
    });

    Route::prefix('accounts')->name('accounts.')->group(function () {
        // 🚀 DÉPÔT RAPIDE
        Route::get('quick-deposit', [AgentAccountController::class, 'quickDepositForm'])->name('quick-deposit');
        Route::get('quick-deposit-search', [AgentAccountController::class, 'quickDepositSearch'])->name('quick-deposit-search');
        Route::post('{account}/quick-deposit', [AgentAccountController::class, 'processQuickDeposit'])->name('quick-deposit.process');

        Route::get('/', [AgentAccountController::class, 'index'])->name('index');
        Route::get('create/{client}', [AgentAccountController::class, 'create'])->name('create');
        Route::post('store/{client}', [AgentAccountController::class, 'store'])->name('store');
        Route::get('{account}', [AgentAccountController::class, 'show'])->name('show');

        // Activation
        Route::get('{account}/activate', [AgentAccountController::class, 'activateForm'])->name('activate.form');
        Route::post('{account}/activate', [AgentAccountController::class, 'activate'])->name('activate');

        // Dépôts classiques
        Route::get('{account}/deposit', [AgentAccountController::class, 'depositForm'])->name('deposit.form');
        Route::post('{account}/deposit', [AgentAccountController::class, 'processDeposit'])->name('deposit.process');

        // Transactions
        Route::get('{account}/transactions', [AgentAccountController::class, 'transactions'])->name('transactions');
    });
    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [AgentTransactionController::class, 'index'])->name('index');
        Route::get('/create', [AgentTransactionController::class, 'create'])->name('create');
        Route::post('/', [AgentTransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}', [AgentTransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/receipt', [AgentTransactionController::class, 'receipt'])->name('receipt');
    });


    // Dépôts rapides
    Route::post('/quick-deposit', [AgentTransactionController::class, 'quickDeposit'])->name('quick.deposit');
    Route::post('/quick-withdrawal', [AgentTransactionController::class, 'quickWithdrawal'])->name('quick.withdrawal');


    // Rapports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/daily', [AgentReportController::class, 'daily'])->name('daily');
        Route::get('/weekly', [AgentReportController::class, 'weekly'])->name('weekly');
        Route::get('/monthly', [AgentReportController::class, 'monthly'])->name('monthly');
        Route::get('/export', [AgentReportController::class, 'export'])->name('export');
    });

});
