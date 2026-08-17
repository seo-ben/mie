<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\ClientAuthController;
use App\Http\Controllers\Web\Shared\FileDownloadController;

// ======================
// ROUTES PUBLIQUES
// ======================

// Page d'accueil publique FSD-YAYRA
Route::get('/', function () {
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role === 'client') {
            return redirect()->route('client.dashboard');
        }
        
        if (in_array($user->role, ['agent_terrain', 'agent_agence'])) {
            return redirect()->route('agent.dashboard');
        }

        if ($user->role === 'caissier') {
            return redirect()->route('caissier.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
    
    return view('welcome');
})->name('home');

// Redirect standard /login route to home so normal visitors cannot detect the login page
Route::get('/login', function () {
    return redirect()->route('home');
});

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
// AUTHENTIFICATION SÉCURISÉE & DISCRÈTE
// ======================

Route::middleware('guest')->group(function () {
    // Connexion système via URL secrète (Agents, Gestionnaires, Admins)
    Route::get('fsd-portal-access', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('fsd-portal-access', [AuthController::class, 'login']);

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

    // Espace Client
    require __DIR__ . '/web/client.php';

    // Espace Agent
    require __DIR__ . '/web/agent.php';

    // Espace Caissier
    require __DIR__ . '/web/cashier.php';

    // Espace Administrateur & Manager
    require __DIR__ . '/web/admin.php';

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
