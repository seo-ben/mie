<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Agent\AgentCashierController;
use App\Http\Controllers\Web\Agent\AgentClientController;
use App\Http\Controllers\Web\Agent\AgentAccountController;
use App\Http\Controllers\Web\Agent\AgentTransactionController;

Route::middleware(['role:caissier,administrateur_systeme,administrateur_reglementaire'])->prefix('caissier')->name('caissier.')->group(function () {

    // Dashboard Caissier
    Route::get('/dashboard', [AgentCashierController::class, 'dashboard'])->name('dashboard');

    // Terminal de Caisse (Journal des Opérations & Clôture)
    Route::get('/terminal', [AgentCashierController::class, 'terminal'])->name('terminal');
    Route::get('/logs', [AgentCashierController::class, 'operationLogs'])->name('logs');
    Route::get('/session/close', [AgentCashierController::class, 'closeSessionForm'])->name('session.close.form');
    Route::post('/session/{session}/close', [AgentCashierController::class, 'closeSession'])->name('session.close');

    // Ouverture de Session de Caisse par le Caissier
    Route::post('/session/open', [AgentCashierController::class, 'openSession'])->name('session.open');

    // Virement Trésorerie / Coffre-fort (Approvisionnement & Décharge directe au guichet)
    Route::post('/session/vault-transfer', [AgentCashierController::class, 'vaultTransfer'])->name('session.vault-transfer');

    // Annulation / Extourne de Transaction avec PIN Superviseur (Reversal)
    Route::post('/transactions/reversal', [AgentCashierController::class, 'reverseTransaction'])->name('transactions.reversal.direct');
    Route::post('/transactions/{transaction}/reversal', [AgentCashierController::class, 'reverseTransaction'])->name('transactions.reversal');

    // Encaissement (Dépôts)
    Route::get('/depot', [AgentCashierController::class, 'depotForm'])->name('depot');
    Route::get('/depot/search', [AgentCashierController::class, 'depotSearch'])->name('depot.search');
    Route::post('/depot/{account}/process', [AgentCashierController::class, 'processDeposit'])->name('depot.process');

    // Décaissement (Retraits)
    Route::get('/retrait', [AgentCashierController::class, 'retraitForm'])->name('retrait');
    Route::get('/retrait/search', [AgentCashierController::class, 'retraitSearch'])->name('retrait.search');
    Route::post('/retrait/{account}/process', [AgentCashierController::class, 'processWithdrawal'])->name('retrait.process');

    // Virement Inter-Comptes Client
    Route::get('/transfer', [AgentCashierController::class, 'transferForm'])->name('transfer');
    Route::post('/transfer/process', [AgentCashierController::class, 'processTransfer'])->name('transfer.process');

    // Supervision & Collectes des Agents de Terrain
    Route::get('/field-agents', [AgentCashierController::class, 'fieldAgents'])->name('field-agents.index');
    Route::get('/field-agents/{agent}', [AgentCashierController::class, 'fieldAgentDetails'])->name('field-agents.show');

    // Module Crédits au Guichet (Liste, Simulation, Éligibilité, Demande, Décaissement, Remboursement)
    Route::get('/loans', [AgentCashierController::class, 'loansIndex'])->name('loans.index');
    Route::get('/loans/apply', [AgentCashierController::class, 'loanApplyForm'])->name('loans.apply.form');
    Route::post('/loans/apply', [AgentCashierController::class, 'processLoanApply'])->name('loans.apply');
    Route::get('/loans/check-eligibility/{client}', [AgentCashierController::class, 'checkEligibility'])->name('loans.eligibility');
    Route::post('/loans/simulate', [AgentCashierController::class, 'simulateLoan'])->name('loans.simulate');
    Route::get('/loans/{loan}', [AgentCashierController::class, 'loanDetails'])->name('loans.show');
    Route::post('/loans/{loan}/verifications', [AgentCashierController::class, 'updateLoanVerifications'])->name('loans.verifications.update');
    Route::get('/prets/disbursement', [AgentCashierController::class, 'loanDisbursementList'])->name('loans.disbursement');
    Route::post('/prets/{loan}/disburse', [AgentCashierController::class, 'processLoanDisbursement'])->name('loans.disburse');
    Route::get('/prets/{loan}/schedule', [AgentCashierController::class, 'loanSchedule'])->name('loans.schedule');
    Route::post('/loans/{loan}/repay', [AgentCashierController::class, 'processLoanRepay'])->name('loans.repay');

    // Impression
    Route::get('/receipt/{transaction}', [AgentCashierController::class, 'printReceipt'])->name('receipt.print');

    // Rapports Avancés de Caisse
    Route::get('/reports', [AgentCashierController::class, 'reports'])->name('reports');

    // ============================================================
    //  GESTION CLIENTS & COMPTES AU GUICHET
    // ============================================================

    // Gestion des Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [AgentClientController::class, 'index'])->name('index');
        Route::get('create', [AgentClientController::class, 'create'])->name('create');
        Route::post('store', [AgentClientController::class, 'store'])->name('store');
        
        // Inscription express avec tontine
        Route::get('register-with-tontine', [AgentCashierController::class, 'registerWithTontineForm'])->name('register-with-tontine');
        Route::post('register-with-tontine', [AgentCashierController::class, 'storeWithTontine'])->name('register-with-tontine.store');

        Route::get('{client}', [AgentClientController::class, 'show'])->name('show');
        Route::get('{client}/edit', [AgentClientController::class, 'edit'])->name('edit');
        Route::put('{client}', [AgentClientController::class, 'update'])->name('update');

        // Validation KYC Express
        Route::post('{client}/approve-kyc', [AgentCashierController::class, 'approveClientKyc'])->name('approve-kyc');
    });

    // Gestion des Comptes Tontine & Épargne
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AgentAccountController::class, 'index'])->name('index');
        Route::get('create/{client}', [AgentCashierController::class, 'createAccountForm'])->name('create');
        Route::post('store/{client}', [AgentCashierController::class, 'storeAccount'])->name('store');
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
