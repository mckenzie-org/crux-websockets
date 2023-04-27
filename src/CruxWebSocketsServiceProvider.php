<?php
namespace Etlok\Crux\WebSockets;

use Etlok\Crux\WebSockets\ChannelManagers\LocalChannelManager;
use Etlok\Crux\WebSockets\Console\BuildWebSocketController;
use Etlok\Crux\WebSockets\Console\InstallCruxWebSockets;
use Etlok\Crux\WebSockets\Console\StartServer;
use Etlok\Crux\WebSockets\Contracts\ChannelManager;
use Etlok\Crux\WebSockets\Server\Router;
use Etlok\Crux\WebSockets\Services\WebsocketBroadcast;
use Etlok\Crux\WebSockets\Services\WebsocketRoute;
use Etlok\Crux\WebSockets\Services\WebsocketService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class CruxWebSocketsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->registerFacades();

        if ($this->app->runningInConsole()) {

            $this->commands([
                StartServer::class,
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

    public function registerFacades()
    {
        $this->app->singleton(LoopInterface::class, function () {
            return Loop::get();
        });
        $this->app->singleton(ChannelManager::class, function ($app) {
            $mode = config('crux_websockets.replication.mode');
            $channel_manager = config("crux_websockets.replication.modes.{$mode}.channel_manager");

            return ($channel_manager ?? null) !== null && class_exists($channel_manager)
                ? app($channel_manager) : new LocalChannelManager();
        });
        $this->app->singleton('websockets.service', function () {
            return app(config('crux_websockets.service'));
        });
        $this->app->singleton('websockets.router', function () {
            return new Router;
        });
        $this->app->singleton('websockets.route', function () {
            return new WebsocketRoute;
        });
        $this->app->singleton('websockets.broadcaster', function () {
            return new WebsocketBroadcast;
        });

    }
}