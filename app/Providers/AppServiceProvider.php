<?php

namespace App\Providers;

use App\Auth\JwtClaimFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('tymon.jwt.claim.factory', function ($app) {
            $factory = new JwtClaimFactory($app['request']);
            $app->refresh('request', $factory, 'setRequest');

            return $factory->setTTL(config('jwt.ttl'))
                ->setLeeway(config('jwt.leeway'));
        });

        $this->app->singleton(ImageManager::class, fn () => new ImageManager(
            Driver::class,
            autoOrientation: false,
            decodeAnimation: false,
        ));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
