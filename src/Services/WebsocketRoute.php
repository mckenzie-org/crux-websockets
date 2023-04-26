<?php

namespace Etlok\Crux\WebSockets\Services;

use Etlok\Crux\WebSockets\Exceptions\BadWebSocketMethodException;
use Illuminate\Support\Facades\App;

class WebsocketRoute {

    protected $routes = [];
    protected $controllers = [];

    public function add($message, $action)
    {
        $this->routes[$message] = $action;
    }

    public function canResolve($m)
    {
        if(!isset($this->routes[$m]) || !isset($this->routes[$m][1])) {
            return false;
        }
        return true;
    }

    public function controller($m, $handler)
    {
        if(!$this->canResolve($m)) {
            throw new BadWebSocketMethodException("Method A Not Found", 404);
        }
        $cls = $this->routes[$m][0];
        $method = $this->routes[$m][1];
        if(!isset($this->controllers[$cls])) {
            $this->controllers[$cls] = App::make($cls,['handler'=>$handler]);
        }
        return $this->controllers[$cls];
    }

    public function method($m)
    {
        if(!$this->canResolve($m)) {
            throw new BadWebSocketMethodException("Method B Not Found", 404);
        }
        $method = $this->routes[$m][1];
        return $method;
    }

}
