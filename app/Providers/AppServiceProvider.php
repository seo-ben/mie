<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ViewComposers\AdminLayoutComposer;
use Illuminate\Support\Facades\View;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr');
        View::composer('layouts.app_admin', AdminLayoutComposer::class);
    }
}
