<?php
namespace Etlok\Crux\WebSockets\Services;

use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
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
        $connection->app->id = 'cyclops';

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
        // TODO: Implement onOpen() method.

        $this->verifyApp($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);
        /*
        $payload = new \stdClass();
        $payload->channel = 'test';
        $this->subscribe($connection, $payload);
        */

    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->handlers[$connection->app->project]['handler']->closeConnection($connection);
        // TODO: Implement onClose() method.
        $this->channelManager->removeFromAllChannels($connection);
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
        // TODO: Implement onMessage() method.
        //$channel = $this->channelManager->find($connection->app->id, 'test');
        /*
        optional($channel)->broadcast([
            'status'=>0,
            'event'=>'message_sent',
            'message'=>'Message Sent'
        ]);
        */
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

    protected function subscribe(ConnectionInterface $connection, \stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);
        $channel->subscribe($connection, $payload);
    }

}
