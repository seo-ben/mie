<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Agent\AgentCashierController;
use App\Http\Controllers\Web\Agent\AgentClientController;
use App\Http\Controllers\Web\Agent\AgentAccountController;
use App\Http\Controllers\Web\Agent\AgentTransactionController;

Route::middleware(['role:caissier'])->prefix('caissier')->name('caissier.')->group(function () {

    // Dashboard Caissier
    Route::get('/dashboard', [AgentCashierController::class, 'dashboard'])->name('dashboard');

    // Terminal de Caisse (Journal des Opérations)
    Route::get('/terminal', [AgentCashierController::class, 'terminal'])->name('terminal');
    Route::get('/logs', [AgentCashierController::class, 'operationLogs'])->name('logs');
    Route::get('/session/close', [AgentCashierController::class, 'closeSessionForm'])->name('session.close.form');
    Route::post('/session/{session}/close', [AgentCashierController::class, 'closeSession'])->name('session.close');

    // Encaissement (Dépôts)
    Route::get('/depot', [AgentCashierController::class, 'depotForm'])->name('depot');
    Route::get('/depot/search', [AgentCashierController::class, 'depotSearch'])->name('depot.search');
    Route::post('/depot/{account}/process', [AgentCashierController::class, 'processDeposit'])->name('depot.process');

    // Décaissement (Retraits & Prêts)
    Route::get('/retrait', [AgentCashierController::class, 'retraitForm'])->name('retrait');
    Route::get('/retrait/search', [AgentCashierController::class, 'retraitSearch'])->name('retrait.search');
    Route::post('/retrait/{account}/process', [AgentCashierController::class, 'processWithdrawal'])->name('retrait.process');
    
    // NOUVEAU: Décaissement de Prêts
    Route::get('/prets/disbursement', [AgentCashierController::class, 'loanDisbursementList'])->name('loans.disbursement');
    Route::post('/prets/{loan}/disburse', [AgentCashierController::class, 'processLoanDisbursement'])->name('loans.disburse');
    Route::get('/prets/{loan}/schedule', [AgentCashierController::class, 'loanSchedule'])->name('loans.schedule');

    // Impression
    Route::get('/receipt/{transaction}', [AgentCashierController::class, 'printReceipt'])->name('receipt.print');

    // ============================================================
    //  HÉRITAGE AGENT TERRAIN (Le caissier peut aussi faire ça)
    // ============================================================

    // Gestion des Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [AgentClientController::class, 'index'])->name('index');
        Route::get('create', [AgentClientController::class, 'create'])->name('create');
        Route::post('store', [AgentClientController::class, 'store'])->name('store');
        
        // NOUVEAU : Inscription express avec tontine
        Route::get('register-with-tontine', [AgentCashierController::class, 'registerWithTontineForm'])->name('register-with-tontine');
        Route::post('register-with-tontine', [AgentCashierController::class, 'storeWithTontine'])->name('register-with-tontine.store');

        Route::get('{client}', [AgentClientController::class, 'show'])->name('show');
        Route::get('{client}/edit', [AgentClientController::class, 'edit'])->name('edit');
        Route::put('{client}', [AgentClientController::class, 'update'])->name('update');
    });

    // Gestion des Comptes Tontine
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AgentAccountController::class, 'index'])->name('index');
        Route::get('create/{client}', [AgentAccountController::class, 'create'])->name('create');
        Route::post('store/{client}', [AgentAccountController::class, 'store'])->name('store');
        Route::get('{account}', [AgentAccountController::class, 'show'])->name('show');
        Route::get('{account}/deposit', [AgentAccountController::class, 'depositForm'])->name('deposit.form');
        Route::post('{account}/deposit', [AgentAccountController::class, 'processDeposit'])->name('deposit.process');
        Route::get('{account}/transactions', [AgentAccountController::class, 'transactions'])->name('transactions');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [AgentTransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [AgentTransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/receipt', [AgentTransactionController::class, 'receipt'])->name('receipt');
    });
});
