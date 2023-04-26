<?php

use Etlok\Crux\WebSockets\Facades\WebSocketRouter;
use Etlok\Crux\WebSockets\Services\WebsocketService;

WebSocketRouter::webSocket('/websocket/{project}/{entity}/{entity_id}', WebsocketService::class);
