<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ClientAuthController;
use App\Http\Controllers\Api\Client\ClientDashboardController;
use App\Http\Controllers\Api\Client\ClientProfileController;
use App\Http\Controllers\Api\Client\ClientAccountController;
use App\Http\Controllers\Api\Client\ClientTransactionController;
use App\Http\Controllers\Api\Client\ClientLoanController;
use App\Http\Controllers\Api\Client\ClientTontineController;
use App\Http\Controllers\Api\Client\ClientNotificationController;
use App\Http\Controllers\Api\Agent\AgentDashboardController;
use App\Http\Controllers\Api\Agent\AgentClientController;
use App\Http\Controllers\Api\Agent\AgentAccountController;
use App\Http\Controllers\Api\Agent\AgentTransactionController;
use App\Http\Controllers\Api\Agent\AgentReportController;
use App\Http\Controllers\Api\Manager\ManagerDashboardController;
use App\Http\Controllers\Api\Manager\ManagerKYCController;
use App\Http\Controllers\Api\Manager\ManagerLoanController;
use App\Http\Controllers\Api\Manager\ManagerTransactionController;
use App\Http\Controllers\Api\Manager\ManagerReportController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminClientController;
use App\Http\Controllers\Api\Admin\AdminAccountController;
use App\Http\Controllers\Api\Admin\AdminTransactionController;
use App\Http\Controllers\Api\Admin\AdminLoanController;
use App\Http\Controllers\Api\Admin\AdminTontineController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminAgencyController;
use App\Http\Controllers\Api\Admin\AdminConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes pour l'application mobile
| Base URL: /api/v1
| Authentication: Sanctum (Bearer Token)
|
*/

Route::prefix('v1')->group(function () {

    // ======================
    // ROUTES PUBLIQUES
    // ======================

    // Informations générales
    Route::get('/info', function () {
        return response()->json([
            'app_name' => config('app.name'),
            'version' => '1.0.0',
            'api_version' => 'v1'
        ]);
    });

    // ======================
    // AUTHENTIFICATION
    // ======================

    Route::prefix('auth')->group(function () {

        // Connexion système (Agents, Gestionnaires, Admins)
        Route::post('login', [AuthController::class, 'login']);

        // Connexion client
        Route::post('client/login', [ClientAuthController::class, 'login']);

        // Inscription client
        Route::post('client/register', [ClientAuthController::class, 'register']);

        // Vérification OTP
        Route::post('client/verify-otp', [ClientAuthController::class, 'verifyOtp']);
        Route::post('client/resend-otp', [ClientAuthController::class, 'resendOtp']);

        // Mot de passe oublié
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('client/forgot-password', [ClientAuthController::class, 'forgotPassword']);

        // Réinitialisation mot de passe
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('client/reset-password', [ClientAuthController::class, 'resetPassword']);

        // Définir mot de passe (premier accès client)
        Route::post('client/set-password', [ClientAuthController::class, 'setPassword']);
    });

    // ======================
    // ROUTES PROTÉGÉES (AUTH REQUIRED)
    // ======================

    Route::middleware(['auth:sanctum'])->group(function () {

        // Déconnexion
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Profil utilisateur
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('auth/upload-avatar', [AuthController::class, 'uploadAvatar']);

        // Rafraîchir le token
        Route::post('auth/refresh', [AuthController::class, 'refresh']);

        // ======================
        // ESPACE CLIENT
        // ======================

        Route::middleware(['role:client'])->prefix('client')->group(function () {

            // Dashboard
            Route::get('dashboard', [ClientDashboardController::class, 'index']);
            Route::get('dashboard/stats', [ClientDashboardController::class, 'stats']);
            Route::get('dashboard/recent-activities', [ClientDashboardController::class, 'recentActivities']);

            // Profil
            Route::prefix('profile')->group(function () {
                Route::get('/', [ClientProfileController::class, 'show']);
                Route::put('/', [ClientProfileController::class, 'update']);
                Route::post('upload-document', [ClientProfileController::class, 'uploadDocument']);
                Route::delete('document/{id}', [ClientProfileController::class, 'deleteDocument']);
                Route::get('kyc-status', [ClientProfileController::class, 'kycStatus']);
                Route::get('documents', [ClientProfileController::class, 'documents']);
            });

            // Comptes
            Route::prefix('accounts')->group(function () {
                Route::get('/', [ClientAccountController::class, 'index']);
                Route::post('/', [ClientAccountController::class, 'store']);
                Route::get('{account}', [ClientAccountController::class, 'show']);
                Route::get('{account}/balance', [ClientAccountController::class, 'balance']);
                Route::get('{account}/transactions', [ClientAccountController::class, 'transactions']);
                Route::post('{account}/activate', [ClientAccountController::class, 'activate']);
            });

            // Transactions
            Route::prefix('transactions')->group(function () {
                Route::get('/', [ClientTransactionController::class, 'index']);
                Route::post('deposit', [ClientTransactionController::class, 'deposit']);
                Route::post('withdrawal', [ClientTransactionController::class, 'withdrawal']);
                Route::post('transfer', [ClientTransactionController::class, 'transfer']);
                Route::get('{transaction}', [ClientTransactionController::class, 'show']);
                Route::get('{transaction}/receipt', [ClientTransactionController::class, 'receipt']);
                Route::post('{transaction}/download-receipt', [ClientTransactionController::class, 'downloadReceipt']);
            });

            // Prêts
            Route::prefix('loans')->group(function () {
                Route::get('/', [ClientLoanController::class, 'index']);
                Route::post('/', [ClientLoanController::class, 'store']);
                Route::post('simulate', [ClientLoanController::class, 'simulate']);
                Route::get('eligibility', [ClientLoanController::class, 'eligibility']);
                Route::get('{loan}', [ClientLoanController::class, 'show']);
                Route::get('{loan}/schedule', [ClientLoanController::class, 'schedule']);
                Route::post('{loan}/payment', [ClientLoanController::class, 'payment']);
            });

            // Tontines
            Route::prefix('tontines')->group(function () {
                Route::get('/', [ClientTontineController::class, 'index']);
                Route::get('{tontine}', [ClientTontineController::class, 'show']);
                Route::get('{tontine}/cycles', [ClientTontineController::class, 'cycles']);
                Route::get('{tontine}/members', [ClientTontineController::class, 'members']);
                Route::post('{tontine}/payment', [ClientTontineController::class, 'payment']);
            });

            // Notifications
            Route::prefix('notifications')->group(function () {
                Route::get('/', [ClientNotificationController::class, 'index']);
                Route::get('unread-count', [ClientNotificationController::class, 'unreadCount']);
                Route::put('{notification}/read', [ClientNotificationController::class, 'markAsRead']);
                Route::put('mark-all-read', [ClientNotificationController::class, 'markAllAsRead']);
                Route::delete('{notification}', [ClientNotificationController::class, 'delete']);
            });
        });

        // ======================
        // ESPACE AGENT
        // ======================

        Route::middleware(['role:agent_terrain,agent_agence'])->prefix('agent')->group(function () {

            // Dashboard
            Route::get('dashboard', [AgentDashboardController::class, 'index']);
            Route::get('dashboard/stats', [AgentDashboardController::class, 'stats']);
            Route::get('dashboard/daily-stats', [AgentDashboardController::class, 'dailyStats']);
            Route::get('dashboard/performance', [AgentDashboardController::class, 'performance']);

            // Gestion des clients
            Route::prefix('clients')->group(function () {
                Route::get('/', [AgentClientController::class, 'index']);
                Route::post('/', [AgentClientController::class, 'store']);
                Route::get('search', [AgentClientController::class, 'search']);
                Route::get('{client}', [AgentClientController::class, 'show']);
                Route::put('{client}', [AgentClientController::class, 'update']);
                Route::post('{client}/activate-accounts', [AgentClientController::class, 'activateAccounts']);
                Route::get('/{clientId}/pending-accounts', [AgentClientController::class, 'pendingAccounts'])->name('pending-accounts');
                Route::post('/{clientId}/activate-accounts', [AgentClientController::class, 'activateAccounts'])->name('activate-accounts');

            });
            // Synchronisation WatermelonDB (Offline-First)
            Route::prefix('sync')->group(function () {
                Route::get('pull', [App\Http\Controllers\Api\Agent\AgentSyncController::class, 'pull']);
                Route::post('push', [App\Http\Controllers\Api\Agent\AgentSyncController::class, 'push']);
            });

            // Gestion des comptes
            // Route::prefix('accounts')->group(function () {
            //     Route::get('/', [AgentAccountController::class, 'index']);
            //     Route::post('/', [AgentAccountController::class, 'store']);
            //     Route::post('/{client}', [AgentAccountController::class, 'store']);
            //     Route::get('search', [AgentAccountController::class, 'search']);
            //     Route::get('daily-collection', [AgentAccountController::class, 'dailyCollection']);
            //     Route::get('{account}', [AgentAccountController::class, 'show']);
            //     Route::post('{account}/activate', [AgentAccountController::class, 'activate']);
            //     Route::post('{account}/deposit', [AgentAccountController::class, 'deposit']);
            //     Route::get('{account}/transactions', [AgentAccountController::class, 'transactions']);
            // });


            Route::prefix('accounts')->name('agent.accounts.')->group(function () {
                // Liste et recherche
                Route::get('/', [AgentAccountController::class, 'index'])->name('index');
                Route::get('/search', [AgentAccountController::class, 'search'])->name('search');
                Route::get('/dashboard', [AgentAccountController::class, 'dashboard'])->name('dashboard');

                // Création de compte tontine
                Route::post('/clients/{clientId}', [AgentAccountController::class, 'store'])->name('store');

                // Détails et gestion d'un compte
                Route::get('/{accountId}', [AgentAccountController::class, 'show'])->name('show');
                Route::get('/{accountId}/transactions', [AgentAccountController::class, 'transactions'])->name('transactions');

                // Activation
                Route::post('/{accountId}/activate', [AgentAccountController::class, 'activate'])->name('activate');

                // Dépôts
                Route::post('/{accountId}/deposit', [AgentAccountController::class, 'deposit'])->name('deposit');
            });



            // Dépôt rapide
            Route::prefix('quick')->group(function () {
                Route::post('deposit', [AgentAccountController::class, 'quickDeposit']);
                Route::post('withdrawal', [AgentAccountController::class, 'quickWithdrawal']);
                Route::get('search-account', [AgentAccountController::class, 'searchForQuickTransaction']);
            });

            // Transactions
            Route::prefix('transactions')->group(function () {
                Route::get('/', [AgentTransactionController::class, 'index']);
                Route::post('/', [AgentTransactionController::class, 'store']);
                Route::get('{transaction}', [AgentTransactionController::class, 'show']);
                Route::get('{transaction}/receipt', [AgentTransactionController::class, 'receipt']);
            });



            // Rapports
            // Route::prefix('reports')->group(function () {
            //     Route::get('daily', [AgentReportController::class, 'daily']);
            //     Route::get('weekly', [AgentReportController::class, 'weekly']);
            //     Route::get('monthly', [AgentReportController::class, 'monthly']);
            //     Route::get('custom', [AgentReportController::class, 'custom']);
            // });
        });

        // ======================
        // ESPACE GESTIONNAIRE
        // ======================

        Route::middleware(['role:gestionnaire_superviseur,gestionnaire_credit'])->prefix('manager')->group(function () {

            // Dashboard
            Route::get('dashboard', [ManagerDashboardController::class, 'index']);
            Route::get('dashboard/kpis', [ManagerDashboardController::class, 'kpis']);
            Route::get('dashboard/analytics', [ManagerDashboardController::class, 'analytics']);

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
                Route::get('/', [ManagerLoanController::class, 'index']);
                Route::get('pending', [ManagerLoanController::class, 'pending']);
                Route::get('{loan}', [ManagerLoanController::class, 'show']);
                Route::get('{loan}/analysis', [ManagerLoanController::class, 'analysis']);
                Route::post('{loan}/approve', [ManagerLoanController::class, 'approve']);
                Route::post('{loan}/reject', [ManagerLoanController::class, 'reject']);
                Route::post('{loan}/disburse', [ManagerLoanController::class, 'disburse']);
            });

            // Validation des transactions
            Route::prefix('transactions')->group(function () {
                Route::get('/', [ManagerTransactionController::class, 'index']);
                Route::get('pending', [ManagerTransactionController::class, 'pending']);
                Route::get('{transaction}', [ManagerTransactionController::class, 'show']);
                Route::post('{transaction}/validate', [ManagerTransactionController::class, 'validate']);
                Route::post('{transaction}/reject', [ManagerTransactionController::class, 'reject']);
            });

            // Rapports
            Route::prefix('reports')->group(function () {
                Route::get('agency-performance', [ManagerReportController::class, 'agencyPerformance']);
                Route::get('loan-portfolio', [ManagerReportController::class, 'loanPortfolio']);
                Route::get('collection', [ManagerReportController::class, 'collection']);
                Route::get('agent-performance', [ManagerReportController::class, 'agentPerformance']);
            });
        });

        // ======================
        // ESPACE ADMINISTRATEUR
        // ======================

        // Route::middleware(['role:administrateur_systeme,administrateur_reglementaire'])->prefix('admin')->group(function () {

        //     // Dashboard
        //     Route::get('dashboard', [AdminDashboardController::class, 'index']);
        //     Route::get('dashboard/system-health', [AdminDashboardController::class, 'systemHealth']);
        //     Route::get('dashboard/analytics', [AdminDashboardController::class, 'analytics']);

        //     // Gestion des clients
        //     Route::prefix('clients')->group(function () {
        //         Route::get('/', [AdminClientController::class, 'index']);
        //         Route::post('/', [AdminClientController::class, 'store']);
        //         Route::get('search', [AdminClientController::class, 'search']);
        //         Route::get('stats', [AdminClientController::class, 'stats']);
        //         Route::get('{client}', [AdminClientController::class, 'show']);
        //         Route::put('{client}', [AdminClientController::class, 'update']);
        //         Route::post('{client}/activate', [AdminClientController::class, 'activate']);
        //         Route::post('{client}/deactivate', [AdminClientController::class, 'deactivate']);
        //         Route::post('{client}/approve-kyc', [AdminClientController::class, 'approveKyc']);
        //         Route::post('{client}/reject-kyc', [AdminClientController::class, 'rejectKyc']);
        //     });

        //     // Gestion des comptes
        //     Route::prefix('accounts')->group(function () {
        //         Route::get('/', [AdminAccountController::class, 'index']);
        //         Route::post('/', [AdminAccountController::class, 'store']);
        //         Route::get('search', [AdminAccountController::class, 'search']);
        //         Route::get('{account}', [AdminAccountController::class, 'show']);
        //         Route::put('{account}', [AdminAccountController::class, 'update']);
        //         Route::post('{account}/suspend', [AdminAccountController::class, 'suspend']);
        //         Route::post('{account}/reactivate', [AdminAccountController::class, 'reactivate']);
        //         Route::post('{account}/deposit', [AdminAccountController::class, 'deposit']);
        //         Route::post('{account}/withdrawal', [AdminAccountController::class, 'withdrawal']);
        //         Route::post('transfer', [AdminAccountController::class, 'transfer']);
        //         Route::get('{account}/transactions', [AdminAccountController::class, 'transactions']);
        //     });

        //     // Gestion des transactions
        //     Route::prefix('transactions')->group(function () {
        //         Route::get('/', [AdminTransactionController::class, 'index']);
        //         Route::get('analytics', [AdminTransactionController::class, 'analytics']);
        //         Route::get('{transaction}', [AdminTransactionController::class, 'show']);
        //         Route::post('{transaction}/validate', [AdminTransactionController::class, 'validate']);
        //         Route::post('{transaction}/reject', [AdminTransactionController::class, 'reject']);
        //     });

        //     // Gestion des prêts
        //     Route::prefix('loans')->group(function () {
        //         Route::get('/', [AdminLoanController::class, 'index']);
        //         Route::post('/', [AdminLoanController::class, 'store']);
        //         Route::get('search', [AdminLoanController::class, 'search']);
        //         Route::get('{loan}', [AdminLoanController::class, 'show']);
        //         Route::get('{loan}/analyze', [AdminLoanController::class, 'analyze']);
        //         Route::get('{loan}/schedule', [AdminLoanController::class, 'schedule']);
        //         Route::post('{loan}/approve', [AdminLoanController::class, 'approve']);
        //         Route::post('{loan}/reject', [AdminLoanController::class, 'reject']);
        //         Route::post('{loan}/disburse', [AdminLoanController::class, 'disburse']);
        //         Route::post('{loan}/record-payment', [AdminLoanController::class, 'recordPayment']);
        //     });

        //     // Gestion des tontines
        //     Route::prefix('tontines')->group(function () {
        //         Route::get('/', [AdminTontineController::class, 'index']);
        //         Route::get('{tontine}', [AdminTontineController::class, 'show']);
        //         Route::get('{tontine}/contributions', [AdminTontineController::class, 'contributions']);
        //         Route::post('{tontine}/contribute', [AdminTontineController::class, 'contribute']);
        //         Route::post('cycles/{cycle}/close', [AdminTontineController::class, 'closeCycle']);
        //         Route::post('{tontine}/payout', [AdminTontineController::class, 'payout']);
        //     });

        //     // Gestion des utilisateurs
        //     Route::prefix('users')->group(function () {
        //         Route::get('/', [AdminUserController::class, 'index']);
        //         Route::post('/', [AdminUserController::class, 'store']);
        //         Route::get('{user}', [AdminUserController::class, 'show']);
        //         Route::put('{user}', [AdminUserController::class, 'update']);
        //         Route::delete('{user}', [AdminUserController::class, 'destroy']);
        //         Route::post('{user}/reset-password', [AdminUserController::class, 'resetPassword']);
        //         Route::post('{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        //         Route::post('{user}/toggle-2fa', [AdminUserController::class, 'toggle2FA']);
        //     });

        //     // Gestion des agences
        //     Route::prefix('agencies')->group(function () {
        //         Route::get('/', [AdminAgencyController::class, 'index']);
        //         Route::post('/', [AdminAgencyController::class, 'store']);
        //         Route::get('{agency}', [AdminAgencyController::class, 'show']);
        //         Route::put('{agency}', [AdminAgencyController::class, 'update']);
        //         Route::delete('{agency}', [AdminAgencyController::class, 'destroy']);
        //         Route::get('{agency}/users', [AdminAgencyController::class, 'users']);
        //         Route::get('{agency}/stats', [AdminAgencyController::class, 'stats']);
        //     });

        //     // Configuration système
        //     Route::prefix('config')->group(function () {
        //         Route::get('/', [AdminConfigController::class, 'index']);
        //         Route::get('parameters', [AdminConfigController::class, 'parameters']);
        //         Route::put('parameters', [AdminConfigController::class, 'updateParameters']);
        //         Route::post('reset-defaults', [AdminConfigController::class, 'resetDefaults']);
        //     });
        // });
    });
});
