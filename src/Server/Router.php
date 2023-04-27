<?php

namespace Etlok\Crux\WebSockets\Server;

use Etlok\Crux\WebSockets\Exceptions\InvalidWebsocketHandlerException;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    /**
     * The implemented routes.
     *
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $routes;

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->routes = new RouteCollection;
    }

    /**
     * Get the routes.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Register the default routes.
     *
     * @return void
     */
    public function routes()
    {

    }

    /**
     * Add a GET route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function get(string $uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Add a new route to the list.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function addRoute(string $method, string $uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    /**
     * Get the route of a specified method, uri and action.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $action
     * @return \Symfony\Component\Routing\Route
     */
    protected function getRoute(string $method, string $uri, $action): Route
    {
        $action = app($action);
        $action = is_subclass_of($action, MessageComponentInterface::class)
            ? $this->createWebSocketsServer($action)
            : $action;

        return new Route($uri, ['_controller' => $action], [], [], null, [], [$method]);
    }


    public function webSocket(string $uri, $action)
    {
        if (! is_subclass_of($action, MessageComponentInterface::class)) {
            throw InvalidWebsocketHandlerException("WebSocket Handler Not Found",404);
        }

        $this->get($uri, $action);
    }

    /**
     * Create a new websockets server to handle the action.
     *
     * @param  MessageComponentInterface  $app
     * @return \Ratchet\WebSocket\WsServer
     */
    protected function createWebSocketsServer($app): WsServer
    {
        return new WsServer($app);
    }

}