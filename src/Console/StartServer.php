<?php

namespace Etlok\Crux\WebSockets\Console;


use Etlok\Crux\WebSockets\Contracts\ChannelManager;
use Etlok\Crux\WebSockets\Facades\WebSocketsRouter;
use Etlok\Crux\WebSockets\Server\ServerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use function React\Promise\all;

class StartServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve
        {--host=0.0.0.0}
        {--port=6001}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Start the Crux WebSockets server.';

    /**
     * Get the loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * The Pusher server instance.
     *
     * @var \Ratchet\Server\IoServer
     */
    public $server;

    /**
     * Initialize the command.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->loop = Loop::get();
    }

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel->singleton(LoopInterface::class, function () {
            return $this->loop;
        });

        $this->configureManagers();

        $this->configureRestartTimer();

        $this->configureRoutes();

        $this->configurePcntlSignal();

        $this->configurePongTracker();

        $this->startServer();
    }

    /**
     * Register the managers that are not resolved
     * in the package service provider.
     *
     * @return void
     */
    protected function configureManagers()
    {
        $this->laravel->singleton(ChannelManager::class, function () {
            $mode = config('crux_websockets.replication.mode', 'local');
            $class = config("crux_websockets.replication.modes.{$mode}.channel_manager");
            return new $class($this->loop);
        });
    }

    /**
     * Configure the restart timer.
     *
     * @return void
     */
    public function configureRestartTimer()
    {
        $this->lastRestart = $this->getLastRestart();

        $this->loop->addPeriodicTimer(10, function () {
            if ($this->getLastRestart() !== $this->lastRestart) {
                $this->triggerSoftShutdown();
            }
        });
    }

    /**
     * Register the routes for the server.
     *
     * @return void
     */
    protected function configureRoutes()
    {
        WebSocketsRouter::routes();
    }

    /**
     * Configure the PCNTL signals for soft shutdown.
     *
     * @return void
     */
    protected function configurePcntlSignal()
    {
        // When the process receives a SIGTERM or a SIGINT
        // signal, it should mark the server as unavailable
        // to receive new connections, close the current connections,
        // then stopping the loop.

        if (! extension_loaded('pcntl')) {
            return;
        }

        $this->loop->addSignal(SIGTERM, function () {
            $this->line('Closing existing connections...');

            $this->triggerSoftShutdown();
        });

        $this->loop->addSignal(SIGINT, function () {
            $this->line('Closing existing connections...');

            $this->triggerSoftShutdown();
        });
    }

    /**
     * Configure the tracker that will delete
     * from the store the connections that.
     *
     * @return void
     */
    protected function configurePongTracker()
    {
        $this->loop->addPeriodicTimer(10, function () {
            $this->laravel
                ->make(ChannelManager::class)
                ->removeObsoleteConnections();
        });
    }

    /**
     * Start the server.
     *
     * @return void
     */
    protected function startServer()
    {
        $this->info("Starting the WebSocket server on port {$this->option('port')}...");

        $this->buildServer();

        $this->server->run();
    }

    /**
     * Build the server instance.
     *
     * @return void
     */
    protected function buildServer()
    {
        $this->server = new ServerFactory(
            $this->option('host'), $this->option('port')
        );

        $this->server = $this->server
            ->setLoop($this->loop)
            ->withRoutes(WebSocketsRouter::getRoutes())
            ->setConsoleOutput($this->output)
            ->createServer();
    }

    /**
     * Get the last time the server restarted.
     *
     * @return int
     */
    protected function getLastRestart()
    {
        return Cache::get(
            'crux:websockets:restart', 0
        );
    }

    /**
     * Trigger a soft shutdown for the process.
     *
     * @return void
     */
    protected function triggerSoftShutdown()
    {
        $channelManager = $this->laravel->make(ChannelManager::class);

        // Close the new connections allowance on this server.
        $channelManager->declineNewConnections();

        // Get all local connections and close them. They will
        // be automatically be unsubscribed from all channels.
        $channelManager->getLocalConnections()
            ->then(function ($connections) {
                return all(collect($connections)->map(function ($connection) {
                    return app('websockets.service')
                        ->onClose($connection)
                        ->then(function () use ($connection) {
                            $connection->close();
                        });
                })->toArray());
            })
            ->then(function () {
                $this->loop->stop();
            });
    }
}
