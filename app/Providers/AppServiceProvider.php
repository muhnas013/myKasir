<?php

namespace App\Providers;

use App\Policies\MenuPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PosPolicy;
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
        Gate::define('pos.transact', [PosPolicy::class, 'transact']);
        Gate::define('order.view', [OrderPolicy::class, 'view']);
        Gate::define('order.void', [OrderPolicy::class, 'void']);
        Gate::define('menu.manage', [MenuPolicy::class, 'manage']);
        Gate::define('user.manage', [UserPolicy::class, 'manage']);
    }
}
