<?php

namespace App\Providers;

use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('settings.manage', [SettingPolicy::class, 'manage']);
        Gate::define('user.manage', [UserPolicy::class, 'manage']);
    }
}
