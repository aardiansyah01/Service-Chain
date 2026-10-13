<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Workshop;
use Illuminate\Support\Facades\View;

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
        View::composer('admin.layouts.app', function ($view) {
            $view->with(
                'pendingWorkshopCount',
                Workshop::where('status', 'PENDING')->count()
            );
        });
    }
}
