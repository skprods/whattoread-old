<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    protected $namespace = 'App\\Http\\Controllers';
    protected $userNamespace = 'App\\Http\\Controllers\\User';
    protected $adminNamespace = 'App\\Http\\Controllers\\Admin';

    public function boot()
    {
        $this->configureRateLimiting();

        Route::prefix('api')
            ->middleware(['api'])
            ->namespace($this->namespace)
            ->group(base_path('routes/auth.php'));

        Route::prefix('api')
            ->middleware(['api', 'auth'])
            ->namespace($this->userNamespace)
            ->group(base_path('routes/user.php'));

        Route::prefix('api/admin/')
            ->middleware(['api', 'auth'])
            ->namespace($this->adminNamespace)
            ->group(base_path('routes/admin.php'));

        Route::prefix('bot')
            ->middleware(['api'])
            ->group(base_path('routes/bot.php'));
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
