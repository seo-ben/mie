<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\AdminConfigController;
use App\Http\Controllers\Web\Admin\AdminUserController;
use App\Http\Controllers\Web\Admin\AdminAgencyController;
use App\Http\Controllers\Web\Admin\AdminReportController;
use App\Http\Controllers\Web\Admin\AdminMonitoringController;
use App\Http\Controllers\Web\Admin\AdminClientController;
use App\Http\Controllers\Web\Admin\AdminAccountController;
use App\Http\Controllers\Web\Admin\AdminTontineController;
use App\Http\Controllers\Web\Admin\AdminTransactionController;
use App\Http\Controllers\Web\Admin\AdminLoanController;
use App\Http\Controllers\Web\Admin\AdminUserReportController;
use App\Http\Controllers\Web\Admin\AdminProfitabilityController;
use App\Http\Controllers\Web\Admin\AdminNotificationController;
use App\Http\Controllers\Web\Admin\CashierSessionController;
use App\Http\Controllers\Web\Admin\AdminPayrollController;
use App\Http\Controllers\Web\Admin\TreasuryController;
use App\Http\Controllers\Web\Manager\ManagerDashboardController;
use App\Http\Controllers\Web\Manager\ManagerKYCController;
use App\Http\Controllers\Web\Manager\ManagerTransactionController;
use App\Http\Controllers\Web\Manager\ManagerReportController;
use App\Http\Controllers\Web\Manager\ManagerTeamController;

Route::middleware(['role:administrateur_systeme,administrateur_reglementaire'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard global
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('system-health', [AdminDashboardController::class, 'systemHealth'])->name('system-health');

    // Configuration système
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', [AdminConfigController::class, 'index'])->name('index');
        Route::get('parameters', [AdminConfigController::class, 'parameters'])->name('parameters');
        Route::post('parameters', [AdminConfigController::class, 'updateParameters'])->name('parameters.update');
        Route::get('integrations', [AdminConfigController::class, 'integrations'])->name('integrations');
        Route::put('integrations', [AdminConfigController::class, 'updateIntegrations'])->name('update-integrations');
        Route::get('backups', [AdminConfigController::class, 'backups'])->name('backups');
        Route::post('backups/create', [AdminConfigController::class, 'createBackup'])->name('backups.create');
        Route::get('backups/download/{file}', [AdminConfigController::class, 'downloadBackup'])->name('backups.download');
        Route::get('admin/config/backups/download/{file}', [AdminConfigController::class, 'downloadBackup'])->name('admin.config.backups.download');
        Route::delete('backups/delete/{file}', [AdminConfigController::class, 'deleteBackup'])->name('backups.delete');
        Route::get('logs', [AdminConfigController::class, 'logs'])->name('logs');
        Route::post('reset-defaults', [AdminConfigController::class, 'resetDefaults'])->name('reset-defaults');
        Route::post('backups/restore', [AdminConfigController::class, 'restoreBackup'])->name('backups.restore');
    });

    // Gestion des utilisateurs
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('create', [AdminUserController::class, 'create'])->name('create');
        Route::post('store', [AdminUserController::class, 'store'])->name('store');
        Route::get('{user}', [AdminUserController::class, 'show'])->name('show');
        Route::get('{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::get('{user}/toggle-2fa', [AdminUserController::class, 'toggle2FA'])->name('toggle-2fa');
        Route::put('{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::get('{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset-password');
        Route::get('{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('{user}/transfer-clients', [AdminUserController::class, 'transferClients'])->name('transfer-clients');
    });

    // Gestion des agences
    Route::prefix('agencies')->name('agencies.')->group(function () {
        Route::get('/', [AdminAgencyController::class, 'index'])->name('index');
        Route::get('/create', [AdminAgencyController::class, 'create'])->name('create');
        Route::post('/', [AdminAgencyController::class, 'store'])->name('store');
        Route::get('/{agency}', [AdminAgencyController::class, 'show'])->name('show');
        Route::get('/{agency}/edit', [AdminAgencyController::class, 'edit'])->name('edit');
        Route::put('/{agency}', [AdminAgencyController::class, 'update'])->name('update');
        Route::delete('/{agency}', [AdminAgencyController::class, 'destroy'])->name('destroy');
        Route::get('/{agency}/users', [AdminAgencyController::class, 'getAgencyUsers'])->name('users');
        Route::get('/{agency}/json', [AdminAgencyController::class, 'getJson'])->name('json');
    });

    // API interne pour charger les utilisateurs
    Route::get('/users-legacy', [AdminUserController::class, 'index'])->name('users.index');

    // Rapports globaux
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('index');
        Route::get('global-dashboard', [AdminReportController::class, 'globalDashboard'])->name('global-dashboard');
        Route::get('regulatory', [AdminReportController::class, 'regulatoryReport'])->name('regulatory');
        Route::get('regulatory/aging', [AdminReportController::class, 'regulatoryAgingReport'])->name('regulatory.aging');
        Route::get('audit-trail', [AdminReportController::class, 'auditTrail'])->name('audit-trail');
        Route::get('system-logs', [AdminReportController::class, 'systemLogs'])->name('system-logs');
        Route::get('export/{type}', [AdminReportController::class, 'export'])->name('export');
        
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserReportController::class, 'index'])->name('index');
            Route::get('/{user}', [AdminUserReportController::class, 'show'])->name('show');
            Route::get('/{user}/export', [AdminUserReportController::class, 'export'])->name('export');
            Route::post('/compare', [AdminUserReportController::class, 'compareUsers'])->name('compare');
        });
        
        Route::prefix('agencies')->name('agencies.')->group(function () {
            Route::get('/', [AdminUserReportController::class, 'agenciesPerformance'])->name('index');
            Route::get('/{agency}', [AdminUserReportController::class, 'agencyReport'])->name('show');
        });
    });

    // Monitoring et audit
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [AdminMonitoringController::class, 'index'])->name('index');
        Route::get('activities', [AdminMonitoringController::class, 'activities'])->name('activities');
        Route::get('security-alerts', [AdminMonitoringController::class, 'securityAlerts'])->name('security-alerts');
        Route::get('performance-metrics', [AdminMonitoringController::class, 'performanceMetrics'])->name('performance-metrics');
    });

    Route::get('kpis', [ManagerDashboardController::class, 'kpis'])->name('kpis');

    // Validation KYC
    Route::prefix('kyc')->name('kyc.')->group(function () {
        Route::get('pending', [ManagerKYCController::class, 'pending'])->name('pending');
        Route::get('{client}', [ManagerKYCController::class, 'show'])->name('show');
        Route::post('{client}/approve', [ManagerKYCController::class, 'approve'])->name('approve');
        Route::post('{client}/reject', [ManagerKYCController::class, 'reject'])->name('reject');
        Route::post('{client}/request-info', [ManagerKYCController::class, 'requestInfo'])->name('request-info');
    });

    // Validation des transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
        Route::get('pending', [ManagerTransactionController::class, 'pending'])->name('pending');
        Route::get('{transaction}', [AdminTransactionController::class, 'show'])->name('show');
        Route::post('{transaction}/validate', [AdminTransactionController::class, 'validatestran'])->name('validate');
        Route::post('{transaction}/reject', [AdminTransactionController::class, 'reject'])->name('reject');
        Route::get('/analytics/dashboard', [AdminTransactionController::class, 'analytics'])->name('analytics');
        Route::get('/export/data', [AdminTransactionController::class, 'export'])->name('export');
        Route::get('/{transaction}/receipt', [AdminTransactionController::class, 'generateReceipt'])->name('receipt');
    });

    // Gestion d'équipe
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [ManagerTeamController::class, 'index'])->name('index');
        Route::get('agents', [ManagerTeamController::class, 'agents'])->name('agents');
        Route::get('agent/{agent}', [ManagerTeamController::class, 'agentDetails'])->name('agent-details');
        Route::get('agent/{agent}/performance', [ManagerTeamController::class, 'agentPerformance'])->name('agent-performance');
    });

    // Gestion des clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('search', [AdminClientController::class, 'search'])->name('search');
        Route::get('stats', [AdminClientController::class, 'stats'])->name('stats');
        Route::get('{client}/activation', [AdminClientController::class, 'activationForm'])->name('activation-form');
        Route::patch('{client}/activate', [AdminClientController::class, 'activateAccounts'])->name('activate-accounts');
        Route::patch('{client}/deactivate', [AdminClientController::class, 'deactivateAccounts'])->name('deactivate-accounts');
        Route::get('{client}/validate-kyc', [AdminClientController::class, 'validateKyc'])->name('validate-kyc');
        Route::post('{client}/approve-kyc', [AdminClientController::class, 'approveKyc'])->name('approve-kyc');
        Route::post('{client}/reject-kyc', [AdminClientController::class, 'rejectKyc'])->name('reject-kyc');
    });
    
    // Impression de reçu (commun à plusieurs opérations)
    Route::get('/receipt/{transaction}', [App\Http\Controllers\Web\Admin\AdminAccountController::class, 'printReceipt'])->name('receipt.print');

    Route::resource('clients', AdminClientController::class)->except(['destroy']);

    // Gestion des comptes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AdminAccountController::class, 'index'])->name('index');
        Route::get('client/{client}/create', [AdminAccountController::class, 'create'])->name('create');
        Route::post('client/{client}', [AdminAccountController::class, 'store'])->name('store');

        // Terminal de Dépôt Rapide
        Route::get('depot', [AdminAccountController::class, 'depotform'])->name('depot');
        Route::get('quick-deposit-search', [AdminAccountController::class, 'quickDepositSearch'])->name('quick-deposit-search');
        Route::post('{account}/quick-deposit', [AdminAccountController::class, 'processQuickDeposit'])->name('quick-deposit.process');

        // Terminal de Retrait Rapide
        Route::get('retrait', [AdminAccountController::class, 'retraitform'])->name('retrait');
        Route::get('quick-withdrawal-search', [AdminAccountController::class, 'quickWithdrawalSearch'])->name('quick-withdrawal-search');
        Route::post('{account}/quick-withdrawal', [AdminAccountController::class, 'processQuickWithdrawal'])->name('quick-withdrawal.process');

        // Transferts et Opérations Classiques
        Route::get('/transfer', [AdminAccountController::class, 'transferForm'])->name('transfer.form');
        Route::post('transfer', [AdminAccountController::class, 'processTransfer'])->name('transfer.process');
        Route::get('search-for-transfer', [AdminAccountController::class, 'searchAccounts'])->name('search-for-transfer');
        Route::get('transfer/history', [AdminAccountController::class, 'transferHistory'])->name('transfer.history');
        Route::get('transfer/{transactionId}/details', [AdminAccountController::class, 'transferDetails'])->name('transfer.details');

        Route::get('withdrawals/history', [AdminAccountController::class, 'withdrawalHistory'])->name('withdrawals.history');
        Route::get('deposits/history', [AdminAccountController::class, 'depositHistory'])->name('deposits.history');

        // Routes Dynamiques pour un Compte Spécifique
        Route::get('{account}', [AdminAccountController::class, 'show'])->name('show');
        Route::get('{account}/edit', [AdminAccountController::class, 'edit'])->name('edit');
        Route::put('{account}', [AdminAccountController::class, 'update'])->name('update');
        Route::post('{account}/suspend', [AdminAccountController::class, 'suspend'])->name('suspend');
        Route::post('{account}/reactivate', [AdminAccountController::class, 'reactivate'])->name('reactivate');
        Route::get('{account}/transactions', [AdminAccountController::class, 'transactions'])->name('transactions');

        Route::get('{account}/withdrawal', [AdminAccountController::class, 'withdrawalForm'])->name('withdrawal.form');
        Route::post('{account}/withdrawal', [AdminAccountController::class, 'processWithdrawal'])->name('withdrawal.process');
        
        Route::get('{account}/deposit', [AdminAccountController::class, 'depositForm'])->name('deposit.form');
        Route::post('{account}/deposit/process', [AdminAccountController::class, 'processDeposit'])->name('deposit.process');
    });

    // Sessions de caisse
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('sessions', [CashierSessionController::class, 'index'])->name('sessions.index');
        Route::get('sessions/create', [CashierSessionController::class, 'create'])->name('sessions.create');
        Route::post('sessions/store', [CashierSessionController::class, 'store'])->name('sessions.store');
        Route::post('sessions/transfer', [CashierSessionController::class, 'transfer'])->name('sessions.transfer');
        Route::post('sessions/{session}/close', [CashierSessionController::class, 'close'])->name('sessions.close');
        Route::get('sessions/{session}', [CashierSessionController::class, 'show'])->name('sessions.show');
        Route::get('sessions/{session}/print', [CashierSessionController::class, 'print'])->name('sessions.print');
    });

    // Gestion de la Trésorerie (Coffre-fort d'Agence)
    Route::prefix('treasury')->name('treasury.')->group(function () {
        Route::get('/', [TreasuryController::class, 'index'])->name('index');
        Route::post('movimento', [TreasuryController::class, 'store'])->name('store');
        Route::post('initialize', [TreasuryController::class, 'initialize'])->name('initialize');
    });

    // Gestion des tontines
    Route::prefix('tontines')->name('tontines.')->group(function () {
        Route::get('/', [AdminTontineController::class, 'index'])->name('index');
        Route::get('/{tontine}', [AdminTontineController::class, 'show'])->name('show');
        Route::get('/{tontine}/contribute', [AdminTontineController::class, 'contributeForm'])->name('contribute-form');
        Route::post('/{tontine}/contribute', [AdminTontineController::class, 'contribute'])->name('contribute');
        Route::get('/{tontine}/contributions', [AdminTontineController::class, 'contributions'])->name('contributions');
        Route::post('/cycles/{cycle}/close', [AdminTontineController::class, 'closeCycle'])->name('cycles.close');
        Route::post('/{tontine}/payout', [AdminTontineController::class, 'payout'])->name('payout');
        Route::get('/reports/global', [AdminTontineController::class, 'report'])->name('report');
        
        // Audit Visuel Interactif
        Route::get('/audit/visual', [AdminTontineController::class, 'visualAudit'])->name('visual-audit');
        Route::get('/audit/visual/data', [AdminTontineController::class, 'visualAuditData'])->name('visual-audit.data');
    });

    // Gestion des prêts
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [AdminLoanController::class, 'index'])->name('index');
        Route::get('search', [AdminLoanController::class, 'search'])->name('search');
        Route::get('create', [AdminLoanController::class, 'create'])->name('create');
        Route::post('store', [AdminLoanController::class, 'store'])->name('store');
        Route::get('{loan}', [AdminLoanController::class, 'show'])->name('show');
        Route::get('{loan}/analyze', [AdminLoanController::class, 'analyze'])->name('analyze');
        Route::get('{loan}/schedule', [AdminLoanController::class, 'schedule'])->name('schedule');
        Route::middleware(['role:administrateur_systeme,administrateur_reglementaire,manager_agence'])->group(function () {
            Route::post('{loan}/approve', [AdminLoanController::class, 'approve'])->name('approve');
            Route::post('{loan}/reject', [AdminLoanController::class, 'reject'])->name('reject');
            Route::post('{loan}/disburse', [AdminLoanController::class, 'disburse'])->name('disburse');
        });
        Route::post('{loan}/record-payment', [AdminLoanController::class, 'recordPayment'])->name('record-payment');
        Route::get('report/global', [AdminLoanController::class, 'report'])->name('report');
        Route::get('report/export', [AdminLoanController::class, 'exportReport'])->name('export-report');
    });

    // Rentabilité
    Route::prefix('profitability')->name('profitability.')->middleware(['auth'])->group(function () {
        Route::get('/', [AdminProfitabilityController::class, 'index'])->name('index');
        Route::get('/fees-report', [AdminProfitabilityController::class, 'feesReport'])->name('fees-report');
        Route::get('/loan-interest-report', [AdminProfitabilityController::class, 'loanInterestReport'])->name('loan-interest');
        Route::get('/tontine-report', [AdminProfitabilityController::class, 'tontineProfitabilityReport'])->name('tontine-report');
        Route::get('/savings-interest-report', [AdminProfitabilityController::class, 'savingsInterestReport'])->name('savings-interest');
        Route::get('/investor-report', [AdminProfitabilityController::class, 'investorReport'])->name('investor-report');
        Route::get('/revenue-breakdown', [AdminProfitabilityController::class, 'revenueBreakdown'])->name('revenue-breakdown');
        Route::get('/cash-flow', [AdminProfitabilityController::class, 'cashFlowDashboard'])->name('cash-flow');
        Route::get('/export-investor-report', [AdminProfitabilityController::class, 'exportInvestorReport'])->name('export-investor');
    });

    // Gestion de la Paie et du Personnel
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [AdminPayrollController::class, 'index'])->name('index');
        Route::get('payment/{user}', [AdminPayrollController::class, 'createPayment'])->name('create-payment');
        Route::post('payment/store', [AdminPayrollController::class, 'storePayment'])->name('store-payment');
        Route::get('report', [AdminPayrollController::class, 'report'])->name('report');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::get('/all', [AdminNotificationController::class, 'all'])->name('all');
        Route::post('/{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('markAllRead');
        Route::post('/create-test', [AdminNotificationController::class, 'createTest'])->name('createTest');
        Route::delete('/{id}', [AdminNotificationController::class, 'destroy'])->name('destroy');
    });

});
