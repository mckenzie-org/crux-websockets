<?php


namespace Etlok\Crux\WebSockets\Server;

use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SecureServer;
use React\Socket\SocketServer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class ServerFactory
{
    /**
     * The host the server will run on.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * The port to run on.
     *
     * @var int
     */
    protected $port = 8080;

    /**
     * The event loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * The routes to register.
     *
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $routes;

    /**
     * Console output.
     *
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    protected $consoleOutput;

    /**
     * Initialize the class.
     *
     * @param  string  $host
     * @param  int  $port
     * @return void
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;

        $this->loop = Loop::get();
    }

    /**
     * Add the routes.
     *
     * @param  \Symfony\Component\Routing\RouteCollection  $routes
     * @return $this
     */
    public function withRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Set the loop instance.
     *
     * @param  \React\EventLoop\LoopInterface  $loop
     * @return $this
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * Set the console output.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $consoleOutput
     * @return $this
     */
    public function setConsoleOutput(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;

        return $this;
    }

    /**
     * Set up the server.
     *
     * @return \Ratchet\Server\IoServer
     */
    public function createServer(): IoServer
    {
        $socket = new SocketServer("{$this->host}:{$this->port}", [], $this->loop);

        if (config('crux_websockets.ssl.local_cert')) {
            $socket = new SecureServer($socket, $this->loop, config('crux_websockets.ssl'));
        }

        $app = new Router(
            new UrlMatcher($this->routes, new RequestContext)
        );

        $httpServer = new \Etlok\Crux\WebSockets\Server\HttpServer($app, config('crux_websockets.max_request_size_in_kb') * 1024);
        return new IoServer($httpServer, $socket, $this->loop);
    }
}