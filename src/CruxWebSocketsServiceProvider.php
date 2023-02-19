<?php
namespace Etlok\Crux\WebSockets;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CruxWebSocketsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->commands([

            ]);

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('crux_websockets.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/./Console/stubs' => base_path('stubs'),
            ], 'stubs');
        }

        Route::prefix(config('crux_websockets.web.prefix'))->middleware(config('crux_websockets.web.middleware'))->group(function() {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

    }
}