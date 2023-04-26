<?php
namespace Etlok\Crux\WebSockets\Services;

use Etlok\Crux\WebSockets\Contracts\ChannelManager;
use Etlok\Crux\WebSockets\Exceptions\ConnectionLimitExceeded;
use Etlok\Crux\WebSockets\Exceptions\OriginNotAllowed;
use Etlok\Crux\WebSockets\Server\QueryParameters;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebsocketService implements MessageComponentInterface
{
    const MESSAGE_TYPE_ERROR = 0;

    protected $channelManager;
    protected $messageHandler = null;
    protected $handlers = [];

    public function __construct(ChannelManager $channelManager)
    {
        $this->handlers = config('crux_websockets.handlers');
        $this->channelManager = $channelManager;
        //$this->messageHandler = new MessageHandler($this->channelManager);
    }

    protected function generateSocketId($connection) {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));
        $connection->socketId = $socketId;
        return $this;
    }

    protected function verifyApp($connection) {

        $connection->app = new \stdClass();
        $connection->app->id = config('crux_websockets.app_id');
        $connection->app->allowedOrigins = config('crux_websockets.allowed_origins');
        $connection->app->capacity = config('crux_websockets.capacity');

        $q = QueryParameters::create($connection->httpRequest);
        $project = $q->get('project');

        if(isset($this->handlers[$project])) {
            if(!isset($this->handlers[$project]['handler'])) {
                $type = $this->handlers[$project]['type'];
                $this->handlers[$project]['handler'] = new $type($this->channelManager,$this->handlers[$project]['routes']);
            }
        } else {
            $type = $this->handlers['default']['type'];
            $this->handlers[$project] = [
                'routes'=>'client.php',
                'type'=>$type,
                'handler'=>null
            ];
            $this->handlers[$project]['handler'] = new $type($this->channelManager,$this->handlers[$project]['routes']);
        }

        $entity = $q->get('entity');
        $entity_id = $q->get('entity_id');

        $connection->app->project = $project;
        $connection->app->entity = $entity;
        $connection->app->entity_id = $entity_id;
        return $this;
    }

    protected function establishConnection($connection) {
        $this->handlers[$connection->app->project]['handler']->openConnection($connection);

        $connection->send(json_encode([
            'status'=>0,
            'author'=>$connection->app->project.':server',
            'channel'=>$connection->app->entity,
            'event' => 'connection_established',
            'data' => []
        ]));

        return $this;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        if (! $this->connectionCanBeMade($connection)) {
            $connection->close();
            return;
        }

        try {
            $this
                ->verifyOrigin($connection)
                ->limitConcurrentConnections($connection)
                ->verifyApp($connection)
                ->generateSocketId($connection)
                ->establishConnection($connection);

            $this->channelManager->subscribeToApp($connection->app->id);
        } catch (\Exception $e) {
            $connection->close();
            /*
            $connection->send(json_encode([
                'status'=>1,
                'author'=>$connection->app->project.':server',
                'channel'=>$connection->app->entity,
                'event' => 'error',
                'messages'=>[
                    [
                        'type'=>self::MESSAGE_TYPE_ERROR,
                        'title'=>$e->getMessage()
                    ]
                ],
                'data' => []
            ]));
           */
        }

    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->handlers[$connection->app->project]['handler']->closeConnection($connection);
        $this->channelManager->unsubscribeFromAllChannels($connection)
            ->then(function() use ($connection) {
                $this->channelManager->unsubscribeFromApp($connection->app->id);
            });

    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function verifyMessage(ConnectionInterface $connection)
    {
        if(!isset($this->handlers[$connection->app->project])) {
            return false;
        }
        if($this->handlers[$connection->app->project]['handler'] === null) return false;

        return true;
    }
    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        if (! isset($connection->app)) {
            return;
        }

        if($this->verifyMessage($connection)) {
            $this->handlers[$connection->app->project]['handler']->handle($connection, $msg);
        } else {
            $connection->send(json_encode([
                'status'=>1,
                'author'=>$connection->app->project.':server',
                'channel'=>$connection->app->entity,
                'event' => 'error',
                'messages'=>[
                    [
                        'type'=>self::MESSAGE_TYPE_ERROR,
                        'title'=>'Unauthorized Request'
                    ]
                ],
                'data' => []
            ]));
        }
    }

    /**
     * Verify the origin.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function verifyOrigin(ConnectionInterface $connection)
    {
        if (! $connection->app->allowedOrigins) {
            return $this;
        }

        $header = (string) ($connection->httpRequest->getHeader('Origin')[0] ?? null);

        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (! $header || ! in_array($origin, $connection->app->allowedOrigins)) {
            throw new OriginNotAllowed("App Origin Not Allowed",401);
        }

        return $this;
    }

    /**
     * Limit the connections count by the app.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function limitConcurrentConnections(ConnectionInterface $connection)
    {
        if (! is_null($capacity = $connection->app->capacity)) {
            $this->channelManager
                ->getGlobalConnectionsCount($connection->app->id)
                ->then(function ($connectionsCount) use ($capacity, $connection) {
                    if ($connectionsCount >= $capacity) {
                        throw new ConnectionLimitExceeded("Connection Limit Exceeded",401);
                    }
                });
        }

        return $this;
    }

    /**
     * Check if the connection can be made for the
     * current server instance.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    protected function connectionCanBeMade(ConnectionInterface $connection): bool
    {
        return $this->channelManager->acceptsNewConnections();
    }

    protected function subscribe(ConnectionInterface $connection, \stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);
        $channel->subscribe($connection, $payload);
    }

}
