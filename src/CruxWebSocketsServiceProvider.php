<?php
namespace Etlok\Crux\WebSockets;

use Etlok\Crux\Console\BuildWebSocketController;
use Etlok\Crux\WebSockets\Console\InstallCruxWebSockets;
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
                BuildWebSocketController::class,
                InstallCruxWebSockets::class
            ]);

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('crux_websockets.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/./Console/stubs' => base_path('stubs'),
            ], 'stubs');

            $this->publishes([
                __DIR__.'/../routes/websockets/client.php' => base_path('routes/websockets/client.php'),
            ], 'routes');
        }

        Route::prefix(config('crux_websockets.web.prefix'))->middleware(config('crux_websockets.web.middleware'))->group(function() {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

    }
}