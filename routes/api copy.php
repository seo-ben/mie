<?php
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::prefix('v1')->group(function () {
    
    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('client/register', [ClientAuthController::class, 'register']);
    });

    // Routes protégées
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Auth communes
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });

        // ======================
        // ROUTES CLIENT (inchangées)
        // ======================
        Route::middleware(['role:client'])->prefix('client')->group(function () {
            Route::get('dashboard', [ClientDashboardController::class, 'index']);
            Route::get('summary', [ClientDashboardController::class, 'summary']);
            
            Route::prefix('profile')->group(function () {
                Route::get('/', [ClientProfileController::class, 'show']);
                Route::put('/', [ClientProfileController::class, 'update']);
                Route::post('upload-document', [ClientProfileController::class, 'uploadDocument']);
                Route::delete('document/{id}', [ClientProfileController::class, 'deleteDocument']);
                Route::get('kyc-status', [ClientProfileController::class, 'kycStatus']);
            });

            Route::prefix('accounts')->group(function () {
                Route::get('/', [ClientAccountController::class, 'index']);
                Route::post('/', [ClientAccountController::class, 'store']);
                Route::get('{account}', [ClientAccountController::class, 'show']);
                Route::get('{account}/balance-history', [ClientAccountController::class, 'balanceHistory']);
                Route::post('{account}/activate', [ClientAccountController::class, 'activate']);
            });

            Route::prefix('transactions')->group(function () {
                Route::get('/', [ClientTransactionController::class, 'index']);
                Route::get('{transaction}', [ClientTransactionController::class, 'show']);
                Route::post('deposit', [ClientTransactionController::class, 'deposit']);
                Route::post('withdrawal', [ClientTransactionController::class, 'withdrawal']);
                Route::get('receipt/{transaction}', [ClientTransactionController::class, 'receipt']);
                Route::get('export', [ClientTransactionController::class, 'export']);
            });

            Route::prefix('loans')->group(function () {
                Route::get('/', [ClientLoanController::class, 'index']);
                Route::post('/', [ClientLoanController::class, 'store']);
                Route::get('{loan}', [ClientLoanController::class, 'show']);
                Route::post('simulate', [ClientLoanController::class, 'simulate']);
                Route::get('{loan}/schedule', [ClientLoanController::class, 'schedule']);
                Route::post('{loan}/payment', [ClientLoanController::class, 'payment']);
                Route::get('eligibility', [ClientLoanController::class, 'eligibility']);
            });

            Route::prefix('tontines')->group(function () {
                Route::get('/', [ClientTontineController::class, 'index']);
                Route::get('{tontine}', [ClientTontineController::class, 'show']);
                Route::get('{tontine}/cycles', [ClientTontineController::class, 'cycles']);
                Route::post('{tontine}/payment', [ClientTontineController::class, 'payment']);
            });

            Route::prefix('notifications')->group(function () {
                Route::get('/', [ClientNotificationController::class, 'index']);
                Route::put('{notification}/read', [ClientNotificationController::class, 'markAsRead']);
                Route::put('mark-all-read', [ClientNotificationController::class, 'markAllAsRead']);
            });
        });

        // ======================
        // ROUTES AGENT (Accessibles par Agent + Manager + Admin)
        // ======================
        Route::middleware(['role:agent_terrain,agent_agence,gestionnaire_superviseur,gestionnaire_credit,administrateur_systeme,administrateur_reglementaire'])
        ->prefix('agent')->group(function () {
            
            // Dashboard agent
            Route::get('dashboard', [AgentDashboardController::class, 'index']);
            Route::get('stats', [AgentDashboardController::class, 'stats']);

            // Gestion des clients
            Route::prefix('clients')->group(function () {
                Route::get('/', [AgentClientController::class, 'index']);
                Route::post('/', [AgentClientController::class, 'store']); // Inscription client
                Route::get('{client}', [AgentClientController::class, 'show']);
                Route::put('{client}', [AgentClientController::class, 'update']);
                Route::get('search', [AgentClientController::class, 'search']);
                Route::post('{client}/activate-accounts', [AgentClientController::class, 'activateAccounts']);
            });

            // Collecte de paiements
            Route::prefix('payments')->group(function () {
                Route::post('collect', [AgentPaymentController::class, 'collect']);
                Route::get('history', [AgentPaymentController::class, 'history']);
                Route::post('validate/{transaction}', [AgentPaymentController::class, 'validate']);
                Route::get('receipt/{transaction}', [AgentPaymentController::class, 'receipt']);
            });

            // Portfolio clients
            Route::prefix('portfolio')->group(function () {
                Route::get('/', [AgentPortfolioController::class, 'index']);
                Route::get('overdue', [AgentPortfolioController::class, 'overdue']);
                Route::post('reminder/{client}', [AgentPortfolioController::class, 'sendReminder']);
            });

            // Synchronisation offline
            Route::prefix('sync')->group(function () {
                Route::post('upload', [AgentSyncController::class, 'upload']);
                Route::get('download', [AgentSyncController::class, 'download']);
                Route::get('status', [AgentSyncController::class, 'status']);
            });
        });

        // ======================
        // ROUTES GESTIONNAIRE (Accessibles par Manager + Admin)
        // ======================
        Route::middleware(['role:gestionnaire_superviseur,gestionnaire_credit,administrateur_systeme,administrateur_reglementaire'])
        ->prefix('manager')->group(function () {
            
            // Dashboard gestionnaire
            Route::get('dashboard', [ManagerDashboardController::class, 'index']);
            Route::get('kpis', [ManagerDashboardController::class, 'kpis']);

            // Validation KYC
            Route::prefix('kyc')->group(function () {
                Route::get('pending', [ManagerKYCController::class, 'pending']);
                Route::get('{client}', [ManagerKYCController::class, 'show']);
                Route::post('{client}/approve', [ManagerKYCController::class, 'approve']);
                Route::post('{client}/reject', [ManagerKYCController::class, 'reject']);
                Route::post('{client}/request-info', [ManagerKYCController::class, 'requestInfo']);
            });

            // Gestion des prêts
            Route::prefix('loans')->group(function () {
                Route::get('pending', [ManagerLoanController::class, 'pending']);
                Route::get('{loan}', [ManagerLoanController::class, 'show']);
                Route::post('{loan}/approve', [ManagerLoanController::class, 'approve']);
                Route::post('{loan}/reject', [ManagerLoanController::class, 'reject']);
                Route::get('{loan}/analysis', [ManagerLoanController::class, 'analysis']);
                Route::post('{loan}/disburse', [ManagerLoanController::class, 'disburse']);
            });

            // Validation des transactions
            Route::prefix('transactions')->group(function () {
                Route::get('pending', [ManagerTransactionController::class, 'pending']);
                Route::post('{transaction}/validate', [ManagerTransactionController::class, 'validate']);
                Route::post('{transaction}/reject', [ManagerTransactionController::class, 'reject']);
            });

            // Rapports
            Route::prefix('reports')->group(function () {
                Route::get('agency-performance', [ManagerReportController::class, 'agencyPerformance']);
                Route::get('loan-portfolio', [ManagerReportController::class, 'loanPortfolio']);
                Route::get('collection-report', [ManagerReportController::class, 'collectionReport']);
                Route::get('agent-performance', [ManagerReportController::class, 'agentPerformance']);
            });

            // Gestion d'équipe
            Route::prefix('team')->group(function () {
                Route::get('agents', [ManagerTeamController::class, 'agents']);
                Route::get('agent/{agent}/performance', [ManagerTeamController::class, 'agentPerformance']);
            });
        });

        // ======================
        // ROUTES ADMINISTRATEUR (Accès total)
        // ======================
        Route::middleware(['role:administrateur_systeme,administrateur_reglementaire'])
        ->prefix('admin')->group(function () {
            
            // Dashboard global
            Route::get('dashboard', [AdminDashboardController::class, 'index']);
            Route::get('system-health', [AdminDashboardController::class, 'systemHealth']);

            // Configuration système
            Route::prefix('config')->group(function () {
                Route::get('parameters', [AdminConfigController::class, 'parameters']);
                Route::put('parameters', [AdminConfigController::class, 'updateParameters']);
                Route::get('integrations', [AdminConfigController::class, 'integrations']);
                Route::put('integrations', [AdminConfigController::class, 'updateIntegrations']);
            });

            // Gestion des utilisateurs
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::post('/', [AdminUserController::class, 'store']);
                Route::get('{user}', [AdminUserController::class, 'show']);
                Route::put('{user}', [AdminUserController::class, 'update']);
                Route::delete('{user}', [AdminUserController::class, 'destroy']);
                Route::post('{user}/reset-password', [AdminUserController::class, 'resetPassword']);
                Route::post('{user}/impersonate', [AdminUserController::class, 'impersonate']); // Nouveau
            });

            // Gestion des agences
            Route::prefix('agencies')->group(function () {
                Route::get('/', [AdminAgencyController::class, 'index']);
                Route::post('/', [AdminAgencyController::class, 'store']);
                Route::put('{agency}', [AdminAgencyController::class, 'update']);
                Route::delete('{agency}', [AdminAgencyController::class, 'destroy']);
            });

            // Rapports globaux
            Route::prefix('reports')->group(function () {
                Route::get('global-dashboard', [AdminReportController::class, 'globalDashboard']);
                Route::get('regulatory-report', [AdminReportController::class, 'regulatoryReport']);
                Route::get('audit-trail', [AdminReportController::class, 'auditTrail']);
                Route::get('system-logs', [AdminReportController::class, 'systemLogs']);
            });

            // Monitoring et audit
            Route::prefix('monitoring')->group(function () {
                Route::get('activities', [AdminMonitoringController::class, 'activities']);
                Route::get('security-alerts', [AdminMonitoringController::class, 'securityAlerts']);
                Route::get('performance-metrics', [AdminMonitoringController::class, 'performanceMetrics']);
            });

            // ========================================
            // ROUTES SPÉCIALES ADMIN - ACCÈS GLOBAL
            // ========================================

            // L'admin peut gérer TOUS les clients (pas seulement ceux de son agence)
            Route::prefix('global-clients')->group(function () {
                Route::get('/', [AdminGlobalController::class, 'allClients']);
                Route::get('{client}', [AdminGlobalController::class, 'clientDetail']);
                Route::put('{client}/force-kyc-approve', [AdminGlobalController::class, 'forceKycApprove']);
                Route::post('{client}/reset-account', [AdminGlobalController::class, 'resetClientAccount']);
                Route::post('{client}/merge-accounts', [AdminGlobalController::class, 'mergeClientAccounts']);
            });

            // L'admin peut gérer TOUS les prêts
            Route::prefix('global-loans')->group(function () {
                Route::get('/', [AdminGlobalController::class, 'allLoans']);
                Route::post('{loan}/force-approve', [AdminGlobalController::class, 'forceApproveLoan']);
                Route::post('{loan}/emergency-disbursement', [AdminGlobalController::class, 'emergencyDisbursement']);
                Route::post('{loan}/write-off', [AdminGlobalController::class, 'writeOffLoan']);
                Route::post('{loan}/restructure', [AdminGlobalController::class, 'restructureLoan']);
            });

            // L'admin peut gérer TOUTES les transactions
            Route::prefix('global-transactions')->group(function () {
                Route::get('/', [AdminGlobalController::class, 'allTransactions']);
                Route::post('{transaction}/reverse', [AdminGlobalController::class, 'reverseTransaction']);
                Route::post('{transaction}/force-complete', [AdminGlobalController::class, 'forceCompleteTransaction']);
                Route::get('suspicious', [AdminGlobalController::class, 'suspiciousTransactions']);
            });

            // L'admin peut agir en tant que n'importe quel rôle
            Route::prefix('act-as')->group(function () {
                Route::post('agent/{agent}', [AdminActAsController::class, 'actAsAgent']);
                Route::post('manager/{manager}', [AdminActAsController::class, 'actAsManager']);
                Route::post('stop-impersonation', [AdminActAsController::class, 'stopImpersonation']);
            });
        });

        // ======================
        // ROUTES COMMUNES ÉTENDUES
        // ======================
        
        // Routes de données partagées (avec permissions selon le rôle)
        Route::prefix('shared')->group(function () {
            
            // Données de base accessibles selon le rôle
            Route::get('clients', [SharedDataController::class, 'clients']); // Filtré selon agence/rôle
            Route::get('accounts', [SharedDataController::class, 'accounts']);
            Route::get('transactions', [SharedDataController::class, 'transactions']);
            Route::get('loans', [SharedDataController::class, 'loans']);
            
            // Statistiques selon les permissions
            Route::get('stats', [SharedDataController::class, 'roleBasedStats']);
        });

        // Upload de fichiers
        Route::post('upload', [FileUploadController::class, 'upload']);
        Route::delete('file/{file}', [FileUploadController::class, 'delete']);

        // Utilitaires
        Route::prefix('utils')->group(function () {
            Route::get('mobile-money-operators', [UtilController::class, 'mobileMoneyOperators']);
            Route::post('verify-mobile-money', [UtilController::class, 'verifyMobileMoneyPayment']);
            Route::get('system-parameters', [UtilController::class, 'systemParameters']);
            Route::get('my-permissions', [UtilController::class, 'myPermissions']); // Nouveau
        });
    });
});