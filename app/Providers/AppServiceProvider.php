<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// ❗️ THÊM import đúng cho URL facade:
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url')); // ép Laravel generate đúng host
        }
    }
}
