<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Définissez ici les chemins ou autres constantes si nécessaire.
     */
    public const HOME = '/home';

    /**
     * Enregistre toutes les routes de l’application.
     */
    public function map(): void
    {
        // Routes API
        $this->mapApiRoutes();

        // Routes Web
        $this->mapWebRoutes();
    }

    /**
     * Déclare les routes API.
     *
     * Toutes les routes définies dans routes/api.php
     * auront automatiquement le préfixe /api et le middleware "api".
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Déclare les routes Web.
     *
     * Elles utilisent le middleware "web" et n’ont pas de préfixe.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
