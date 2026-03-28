<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        Gate::before(function ($user): ?bool {
            return $user->role === UserRole::ADMIN ? true : null;
        });

        Gate::define('view-master-data', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
            UserRole::VIEWER,
        ]));

        Gate::define('manage-master-data', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
        ]));

        Gate::define('view-purchase-invoices', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
            UserRole::VIEWER,
        ]));

        Gate::define('manage-purchase-invoices', fn ($user): bool => $user->hasAnyRole([
            UserRole::ACCOUNTING,
        ]));

        Gate::define('confirm-purchase-invoices', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
        ]));

        Gate::define('view-stock', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
            UserRole::VIEWER,
        ]));

        Gate::define('manage-stock', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
        ]));

        Gate::define('view-reports', fn ($user): bool => $user->hasAnyRole([
            UserRole::WAREHOUSE,
            UserRole::ACCOUNTING,
            UserRole::VIEWER,
        ]));
    }
}
