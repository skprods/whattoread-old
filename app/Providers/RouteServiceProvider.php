<?php

namespace App\Providers;

use App\Models\BookRecsShort;
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
        $this->defineRoutes();
        $this->setBindings();
    }

    private function defineRoutes()
    {
        Route::prefix('api/v1')
            ->middleware(['api'])
            ->namespace($this->namespace)
            ->group(base_path('routes/auth.php'));

        Route::prefix('api/v1')
            ->middleware(['api', 'auth'])
            ->namespace($this->userNamespace)
            ->group(base_path('routes/user.php'));

        Route::prefix('api/v1/admin/')
            ->middleware(['api', 'auth', 'role:admin'])
            ->namespace($this->adminNamespace)
            ->group(base_path('routes/admin.php'));

        Route::prefix('bot')
            ->middleware(['api'])
            ->group(base_path('routes/bot.php'));
    }

    private function setBindings()
    {
        Route::bind('bookRecsShort', function ($bookId) {
            return BookRecsShort::where('book_id', $bookId)->firstOrFail();
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
