 <?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\ClientAuthController;
use App\Http\Controllers\Web\Client\ClientDashboardController;
use App\Http\Controllers\Web\Client\ClientProfileController;
use App\Http\Controllers\Web\Client\ClientAccountController;
use App\Http\Controllers\Web\Client\ClientTransactionController;
use App\Http\Controllers\Web\Client\ClientLoanController;
use App\Http\Controllers\Web\Client\ClientTontineController;
use App\Http\Controllers\Web\Client\ClientNotificationController;
use App\Http\Controllers\Web\Agent\AgentDashboardController;
use App\Http\Controllers\Web\Agent\AgentClientController;
use App\Http\Controllers\Web\Agent\AgentReportController;
use App\Http\Controllers\Web\Agent\AgentTransactionController;
use App\Http\Controllers\Web\Agent\AgentAccountController;
use App\Http\Controllers\Web\Manager\ManagerDashboardController;
use App\Http\Controllers\Web\Manager\ManagerKYCController;
use App\Http\Controllers\Web\Manager\ManagerLoanController;
use App\Http\Controllers\Web\Manager\ManagerTransactionController;
use App\Http\Controllers\Web\Manager\ManagerReportController;
use App\Http\Controllers\Web\Manager\ManagerTeamController;
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
use App\Http\Controllers\Web\Shared\FileDownloadController;

// ======================
// ROUTES PUBLIQUES
// ======================

// Page d'accueil
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Pages informatives
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/services', function () {
    return view('pages.services');
})->name('services');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::get('/session-test', function () {
    session(['test_key' => 'ok']);
    return 'Session créée';
});

Route::get('/session-check', function () {
    return session('test_key', 'aucune session');
});

// ======================
// AUTHENTIFICATION
// ======================

Route::middleware('guest')->group(function () {
    // Connexion système (Agents, Gestionnaires, Admins)
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    // Connexion client
    Route::get('client/login', [ClientAuthController::class, 'showClientLoginForm'])->name('client.login');
    Route::post('client/login', [ClientAuthController::class, 'clientLogin']);

    // Inscription client (auto-inscription)
    Route::get('client/register', [ClientAuthController::class, 'showClientRegistrationForm'])->name('client.register');
    Route::post('client/register', [ClientAuthController::class, 'selfRegister']);

    // Vérification OTP pour clients
    Route::get('client/verify-otp', [ClientAuthController::class, 'showVerifyOtpForm'])->name('client.verify-otp');
    Route::post('client/verify-otp', [ClientAuthController::class, 'verifyOtp']);

    // Définir mot de passe pour clients
    Route::get('client/set-password/{token}', [ClientAuthController::class, 'showSetPasswordForm'])->name('client.set-password');
    Route::post('client/set-password', [ClientAuthController::class, 'setPassword']);

    // Mot de passe oublié (système)
    Route::get('forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.emails');

    // Mot de passe oublié (client)
    Route::get('client/forgot-password', [ClientAuthController::class, 'showClientForgotPasswordForm'])->name('client.password.request');
    Route::post('client/forgot-password', [ClientAuthController::class, 'clientForgotPassword'])->name('client.password.email');

    // Réinitialisation mot de passe (système)
    Route::get('reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // Réinitialisation mot de passe (client)
    Route::get('client/reset-password/{token}', [ClientAuthController::class, 'showClientResetPasswordForm'])->name('client.password.reset');
    Route::post('client/reset-password', [ClientAuthController::class, 'clientResetPassword'])->name('client.password.update');
});

// Déconnexion
Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ======================
// ROUTES PROTÉGÉES
// ======================

Route::middleware(['auth', 'web'])->group(function () {

    // // Redirection selon le rôle après connexion
    // Route::get('/dashboard', function () {
    //     $user = auth()->user();

    //     if ($user->hasRole('client')) {
    //         return redirect()->route('client.dashboard');
    //     } elseif ($user->hasRole(['agent_terrain', 'agent_agence'])) {
    //         return redirect()->route('agent.dashboard');
    //     } elseif ($user->hasRole(['gestionnaire_superviseur', 'gestionnaire_credit'])) {
    //         return redirect()->route('manager.dashboard');
    //     } elseif ($user->hasRole(['administrateur_systeme', 'administrateur_reglementaire'])) {
    //         return redirect()->route('admin.dashboard');
    //     }

    //     abort(403, 'Accès non autorisé');
    // })->name('dashboard');

    // ======================
    // ESPACE CLIENT
    // ======================
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

    // ======================
    // ESPACE AGENT
    // ======================
    Route::middleware(['role:agent_terrain,agent_agence'])->prefix('agent')->name('agent.')->group(function () {

        Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/daily-stats', [AgentDashboardController::class, 'dailyStats'])->name('daily-stats');
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

        // Gestion des comptes
        // Route::prefix('accounts')->name('accounts.')->group(function () {
        //     // Liste et recherche
        //     Route::get('/', [AgentAccountController::class, 'index'])->name('index');
        //     Route::get('/search', [AgentAccountController::class, 'searchAccounts'])->name('search');

        //     // Création de compte (si implémenté)
        //     Route::get('/create', [AgentAccountController::class, 'create'])->name('create');
        //     Route::post('/', [AgentAccountController::class, 'store'])->name('store');

        //     // Dashboard de collecte journalière
        //     Route::get('/daily-collection', [AgentAccountController::class, 'dailyCollectionDashboard'])->name('daily-collection');

        //     // Collecte rapide
        //     Route::get('/quick-deposit', [AgentAccountController::class, 'quickDepositForm'])->name('quick-deposit');
        //     Route::post('/quick-deposit/search', [AgentAccountController::class, 'quickDepositSearch'])->name('quick-deposit.search');

        //     // Historique des dépôts
        //     Route::get('/deposit-history', [AgentAccountController::class, 'depositHistory'])->name('deposit-history');

        //     // Statistiques et export
        //     Route::get('/statistics', [AgentAccountController::class, 'statistics'])->name('statistics');
        //     Route::get('/export', [AgentAccountController::class, 'export'])->name('export');

        //     // Comptes par client
        //     Route::get('/client/{client}', [AgentAccountController::class, 'byClient'])->name('by-client');

        //     // Détails d'un compte spécifique
        //     Route::get('/{account}', [AgentAccountController::class, 'show'])->name('show');
        //     Route::get('/{account}/transactions', [AgentAccountController::class, 'transactions'])->name('transactions');

        //     // Dépôts (collectes)
        //     Route::get('/{account}/deposit', [AgentAccountController::class, 'depositForm'])->name('deposit-form');
        //     Route::post('/{account}/deposit', [AgentAccountController::class, 'processDeposit'])->name('process-deposit');
        // });

        // Route::get('dashboard', [AgentAccountController::class, 'dashboard'])->name('dashboard');

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

    // ======================
    // ESPACE ADMINISTRATEUR
    // ======================
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
            Route::get('admin/config/backups/download/{file}', [AdminConfigController::class, 'downloadBackup'])
                ->name('admin.config.backups.download');
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
        });

        // API interne pour charger les utilisateurs (managers) via AJAX
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');


        // Rapports globaux
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('global-dashboard', [AdminReportController::class, 'globalDashboard'])->name('global-dashboard');
            Route::get('regulatory', [AdminReportController::class, 'regulatoryReport'])->name('regulatory');
            Route::get('audit-trail', [AdminReportController::class, 'auditTrail'])->name('audit-trail');
            Route::get('system-logs', [AdminReportController::class, 'systemLogs'])->name('system-logs');
            Route::get('export/{type}', [AdminReportController::class, 'export'])->name('export');
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

        // // Gestion des prêts
        // Route::prefix('loans')->name('loans.')->group(function () {
        //     Route::get('/', [ManagerLoanController::class, 'index'])->name('index');
        //     Route::get('pending', [ManagerLoanController::class, 'pending'])->name('pending');
        //     Route::get('{loan}', [ManagerLoanController::class, 'show'])->name('show');
        //     Route::get('{loan}/analysis', [ManagerLoanController::class, 'analysis'])->name('analysis');
        //     Route::post('{loan}/approve', [ManagerLoanController::class, 'approve'])->name('approve');
        //     Route::post('{loan}/reject', [ManagerLoanController::class, 'reject'])->name('reject');
        //     Route::post('{loan}/disburse', [ManagerLoanController::class, 'disburse'])->name('disburse');
        // });

        // Validation des transactions
        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [ManagerTransactionController::class, 'index'])->name('index');
            Route::get('pending', [ManagerTransactionController::class, 'pending'])->name('pending');
            Route::get('{transaction}', [ManagerTransactionController::class, 'show'])->name('show');
            Route::post('{transaction}/validate', [ManagerTransactionController::class, 'validate'])->name('validate');
            Route::post('{transaction}/reject', [ManagerTransactionController::class, 'reject'])->name('reject');
        });

        // Rapports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ManagerReportController::class, 'index'])->name('index');
            Route::get('agency-performance', [ManagerReportController::class, 'agencyPerformance'])->name('agency-performance');
            Route::get('loan-portfolio', [ManagerReportController::class, 'loanPortfolio'])->name('loan-portfolio');
            Route::get('collection', [ManagerReportController::class, 'collectionReport'])->name('collection');
            Route::get('agent-performance', [ManagerReportController::class, 'agentPerformance'])->name('agent-performance');
            Route::get('export/{type}', [ManagerReportController::class, 'export'])->name('export');

            Route::prefix('/users')->name('users.')->group(function () {
                Route::get('/', [AdminUserReportController::class, 'index'])->name('index');
                Route::get('/{user}', [AdminUserReportController::class, 'show'])->name('show');
                Route::get('/{user}/export', [AdminUserReportController::class, 'export'])->name('export');
                Route::post('/compare', [AdminUserReportController::class, 'compareUsers'])->name('compare');
            });

            // Rapports agences
            Route::prefix('reports/agencies')->name('agencies.')->group(function () {
                Route::get('/{agency}', [AdminUserReportController::class, 'agencyReport'])->name('show');
            });
        });

        // Gestion d'équipe
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [ManagerTeamController::class, 'index'])->name('index');
            Route::get('agents', [ManagerTeamController::class, 'agents'])->name('agents');
            Route::get('agent/{agent}', [ManagerTeamController::class, 'agentDetails'])->name('agent-details');
            Route::get('agent/{agent}/performance', [ManagerTeamController::class, 'agentPerformance'])->name('agent-performance');
        });




        Route::prefix('clients')->name('clients.')->group(function () {

            Route::get('search', [AdminClientController::class, 'search'])->name('search');

            Route::get('/', [AdminClientController::class, 'index'])->name('index');
            Route::get('create', [AdminClientController::class, 'create'])->name('create');
            Route::post('store', [AdminClientController::class, 'store'])->name('store');
            Route::get('{client}/show', [AdminClientController::class, 'show'])->name('show');
            Route::get('{client}/edit', [AdminClientController::class, 'edit'])->name('edit');
            Route::put('{client}', [AdminClientController::class, 'update'])->name('update');

            Route::get('stats', [AdminClientController::class, 'stats'])->name('stats');

            Route::get('{client}/activation', [AdminClientController::class, 'activationForm'])->name('activation-form');
            Route::patch('{client}/activate', [AdminClientController::class, 'activateAccounts'])->name('activate-accounts');
            Route::patch('{client}/deactivate', [AdminClientController::class, 'deactivateAccounts'])->name('deactivate-accounts');

            Route::get('{client}/validate-kyc', [AdminClientController::class, 'validateKyc'])->name('validate-kyc');
            Route::post('{client}/approve-kyc', [AdminClientController::class, 'approveKyc'])->name('approve-kyc');
            Route::post('{client}/reject-kyc', [AdminClientController::class, 'rejectKyc'])->name('reject-kyc');
        });
                // Routes principales des clients
        Route::resource('clients', AdminClientController::class)->except(['destroy']);

        // Collecte de paiements
        // Route::prefix('payments')->name('payments.')->group(function () {
        //     Route::get('/', [AgentPaymentController::class, 'index'])->name('index');
        //     Route::get('collect', [AgentPaymentController::class, 'showCollectForm'])->name('collect-form');
        //     Route::post('collect', [AgentPaymentController::class, 'collect'])->name('collect');
        //     Route::get('history', [AgentPaymentController::class, 'history'])->name('history');
        //     Route::get('{transaction}', [AgentPaymentController::class, 'show'])->name('show');
        //     Route::post('{transaction}/validate', [AgentPaymentController::class, 'validate'])->name('validate');
        //     Route::get('{transaction}/receipt', [AgentPaymentController::class, 'receipt'])->name('receipt');
        // });


        Route::prefix('accounts')->name('accounts.')->group(function () {
            // Liste de tous les comptes
            Route::get('/', [AdminAccountController::class, 'index'])->name('index');

            // Créer un compte pour un client
            Route::get('client/{client}/create', [AdminAccountController::class, 'create'])->name('create');
            Route::post('client/{client}', [AdminAccountController::class, 'store'])->name('store');

            // 🔥 DÉPÔT RAPIDE
            Route::get('depot', [AdminAccountController::class, 'depotform'])
                ->name('depot');

            Route::get('/quick-deposit-search', [AdminAccountController::class, 'quickDepositSearch'])
                ->name('quick-deposit-search');

            // ✅ AJOUTEZ CETTE LIGNE
            Route::post('{account}/quick-deposit', [AdminAccountController::class, 'processQuickDeposit'])
                ->name('quick-deposit.process');

            // Détails et gestion d'un compte
            Route::get('{account}', [AdminAccountController::class, 'show'])->name('show');
            Route::get('{account}/edit', [AdminAccountController::class, 'edit'])->name('edit');
            Route::put('{account}', [AdminAccountController::class, 'update'])->name('update');

            // Actions sur les comptes
            Route::post('{account}/suspend', [AdminAccountController::class, 'suspend'])->name('suspend');
            Route::post('{account}/reactivate', [AdminAccountController::class, 'reactivate'])->name('reactivate');

            // Transactions d'un compte
            Route::get('{account}/transactions', [AdminAccountController::class, 'transactions'])->name('transactions');

            Route::get('/transfer', [AdminAccountController::class, 'transferForm'])->name('transfer.form');
            Route::post('transfer', [AdminAccountController::class, 'processTransfer'])->name('transfer.process');
            Route::get('transfer/history', [AdminAccountController::class, 'transferHistory'])->name('transfer.history');
            Route::get('transfer/{transaction}', [AdminAccountController::class, 'transferDetails'])->name('transfer.details');

            Route::get('search-for-transfer', [AdminAccountController::class, 'searchAccounts'])->name('search-for-transfer');

            Route::get('{account}/withdrawal', [AdminAccountController::class, 'withdrawalForm'])
                ->name('withdrawal.form');

            Route::post('{account}/withdrawal', [AdminAccountController::class, 'processWithdrawal'])
                ->name('withdrawal.process');

            // Historique des retraits
            Route::get('withdrawals/history', [AdminAccountController::class, 'withdrawalHistory'])
                ->name('withdrawals.history');

            // 🔥 DÉPÔT CLASSIQUE (différent du dépôt rapide)
            Route::get('{account}/deposit', [AdminAccountController::class, 'depositForm'])
                ->name('deposit.form');

            Route::post('{account}/deposit/process', [AdminAccountController::class, 'processDeposit'])
                ->name('deposit.process');

            Route::get('deposits/history', [AdminAccountController::class, 'depositHistory'])
                ->name('deposits.history');
        });

        // Routes pour la gestion des tontines (à ajouter dans le groupe admin)
        Route::prefix('tontines')->name('tontines.')->group(function () {
            // Liste et détails
            Route::get('/', [AdminTontineController::class, 'index'])->name('index');
            Route::get('/{tontine}', [AdminTontineController::class, 'show'])->name('show');

            // Cotisations
            Route::get('/{tontine}/contribute', [AdminTontineController::class, 'contributeForm'])->name('contribute-form');
            Route::post('/{tontine}/contribute', [AdminTontineController::class, 'contribute'])->name('contribute');
            Route::get('/{tontine}/contributions', [AdminTontineController::class, 'contributions'])->name('contributions');

            // Gestion des cycles
            Route::post('/cycles/{cycle}/close', [AdminTontineController::class, 'closeCycle'])->name('cycles.close');
            Route::post('/{tontine}/payout', [AdminTontineController::class, 'payout'])->name('payout');

            // Rapports
            Route::get('/reports/global', [AdminTontineController::class, 'report'])->name('report');
        });


        // Routes pour la gestion des transactions
        Route::prefix('transactions')->name('transactions.')->group(function () {
            // Liste et détails
            Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
            Route::get('/{transaction}', [AdminTransactionController::class, 'show'])->name('show');

            // Analytics
            Route::get('/analytics/dashboard', [AdminTransactionController::class, 'analytics'])->name('analytics');

            // Export
            Route::get('/export/data', [AdminTransactionController::class, 'export'])->name('export');

            // Actions de validation
            Route::post('/{transaction}/validate', [AdminTransactionController::class, 'validatestran'])->name('validate');
            Route::post('/{transaction}/reject', [AdminTransactionController::class, 'reject'])->name('reject');
        });

        // Gestion des prêts
        Route::prefix('loans')->name('loans.')->group(function () {
            // Liste et recherche
            Route::get('/', [AdminLoanController::class, 'index'])->name('index');
            Route::get('search', [AdminLoanController::class, 'search'])->name('search');

            // Création
            Route::get('create', [AdminLoanController::class, 'create'])->name('create');
            Route::post('store', [AdminLoanController::class, 'store'])->name('store');

            // Détails et analyse
            Route::get('{loan}', [AdminLoanController::class, 'show'])->name('show');
            Route::get('{loan}/analyze', [AdminLoanController::class, 'analyze'])->name('analyze');
            Route::get('{loan}/schedule', [AdminLoanController::class, 'schedule'])->name('schedule');

            // Actions sur les prêts (managers et admins seulement)
            Route::middleware(['role:administrateur_systeme,administrateur_reglementaire,manager_agence'])->group(function () {
                Route::post('{loan}/approve', [AdminLoanController::class, 'approve'])->name('approve');
                Route::post('{loan}/reject', [AdminLoanController::class, 'reject'])->name('reject');
                Route::post('{loan}/disburse', [AdminLoanController::class, 'disburse'])->name('disburse');
            });

            // Gestion des paiements
            Route::post('{loan}/record-payment', [AdminLoanController::class, 'recordPayment'])->name('record-payment');

            // Rapports
            Route::get('report/global', [AdminLoanController::class, 'report'])->name('report');
            Route::get('report/export', [AdminLoanController::class, 'exportReport'])->name('export-report');
        });

        // ============================================
        // ROUTES DE RENTABILITÉ & RAPPORTS INVESTISSEURS
        // ============================================

        Route::prefix('profitability')->name('profitability.')->middleware(['auth'])->group(function () {

            // Dashboard principal de rentabilité
            Route::get('/', [AdminProfitabilityController::class, 'index'])
                ->name('index');

            // Rapports détaillés par catégorie
            Route::get('/fees-report', [AdminProfitabilityController::class, 'feesReport'])
                ->name('fees-report');

            Route::get('/loan-interest-report', [AdminProfitabilityController::class, 'loanInterestReport'])
                ->name('loan-interest');

            Route::get('/tontine-report', [AdminProfitabilityController::class, 'tontineProfitabilityReport'])
                ->name('tontine-report');

            Route::get('/savings-interest-report', [AdminProfitabilityController::class, 'savingsInterestReport'])
                ->name('savings-interest');

            // Rapport pour investisseurs
            Route::get('/investor-report', [AdminProfitabilityController::class, 'investorReport'])
                ->name('investor-report');

            // Breakdown des revenus
            Route::get('/revenue-breakdown', [AdminProfitabilityController::class, 'revenueBreakdown'])
                ->name('revenue-breakdown');

            // Cash Flow Dashboard
            Route::get('/cash-flow', [AdminProfitabilityController::class, 'cashFlowDashboard'])
                ->name('cash-flow');

            // Export rapport investisseur
            Route::get('/export-investor-report', [AdminProfitabilityController::class, 'exportInvestorReport'])
                ->name('export-investor');
        });

    });

    // ======================
    // ROUTES COMMUNES
    // ======================

    // Téléchargement de fichiers
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('download/{file}', [FileDownloadController::class, 'download'])->name('download');
        Route::get('view/{file}', [FileDownloadController::class, 'view'])->name('view');
    });

    // Changement de mot de passe
    Route::get('change-password', function () {
        return view('auth.change-password');
    })->name('password.change');

    Route::post('change-password', [AuthController::class, 'changePassword'])->name('password.update-own');
});
