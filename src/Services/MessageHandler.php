<?php

namespace Etlok\Crux\WebSockets\Services;

use App\Crux\Modules\WebSockets\Exceptions\BadWebSocketMethodException;
use App\Crux\Modules\WebSockets\Exceptions\UnAuthenticatedWebSocketCallException;
use App\Crux\Modules\WebSockets\Facades\SocketRoute;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MessageHandler
{
    protected $channelManager;

    public function __construct($channelManager, $routes) {
        $this->channelManager = $channelManager;
        require(base_path('routes/websockets/'.$routes));
    }

    public function closeConnection(ConnectionInterface $connection)
    {

    }

    public function openConnection(ConnectionInterface $connection)
    {

    }


    public function handle(ConnectionInterface $connection, MessageInterface $msg)
    {
        $message = json_decode($msg->getPayload(),true);
        $m = $message['message'];
        try {
            $error = "";
            if(!$this->authenticate($connection, $message, $error)) {
                //$connection->close();
                throw new UnAuthenticatedWebSocketCallException("Authentication Failed:".$error, 401);
            }
            $this->$m($connection, $message);
        } catch (Exception $e) {
            $this->handleException($e, $connection);
        }
    }

    public function ping(ConnectionInterface $connection, Array $message)
    {
        $this->send($connection,[
            'event' => 'pong',
            'data' => []
        ]);
    }

    public function broadcast($connection, $channel, $payload)
    {
        $channel_instance = $this->channelManager->find($connection->app->id, $channel);
        if($channel_instance) {
            $channel_instance->broadcast(array_merge_recursive([
                'status'=>0,
                'event_type'=>'notification',
                'author'=>$connection->app->project.':server',
                'channel'=>$channel,
            ],$payload));
        }
    }

    public function send($connection, $payload)
    {
        $connection->send(json_encode(array_merge_recursive([
            'status'=>0,
            'event_type'=>'notification',
            'author'=>$connection->app->project.':server',
            'channel'=>$connection->app->entity,
        ],$payload)));
    }

    public function handleException($e, $connection)
    {
        $this->send($connection,[
            'event' => 'error',
            'event_type'=>'error',
            'data' => [
                'message'=>[
                    'title'=>'An exception occurred.',
                    'text'=>$e->getMessage(),
                    'code'=>$e->getCode(),
                    'line'=>$e->getLine()
                ]
            ]
        ]);
    }

    public function authenticate($connection, $message, &$error)
    {
        return true;
    }

    public function subscribe($connection, $channel)
    {
        $channelObject = $this->channelManager->findOrCreate($connection->app->id, $channel);
        $channelObject->subscribe($connection,new \stdClass());

    }

    public function findChannel($connection, $channel)
    {
        return $this->channelManager->find($connection->app->id, $channel);
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement __call() method.

        if(SocketRoute::canResolve($name)) {
            call_user_func_array([
                SocketRoute::controller($name,$this),
                SocketRoute::method($name)
            ],$arguments);
        } else {
            throw new BadWebSocketMethodException("Method Not Found", 404);
        }
    }
}
