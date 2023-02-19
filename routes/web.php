<?php

use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Etlok\Crux\WebSockets\Services\WebsocketService;

WebSocketsRouter::webSocket('/websocket/{project}/{entity}/{entity_id}', WebsocketService::class);
