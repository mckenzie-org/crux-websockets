<?php

namespace Etlok\Crux\WebSockets\Facades;

use Illuminate\Support\Facades\Facade;

class WebSocketRouter extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'websocket.router';
    }
}
