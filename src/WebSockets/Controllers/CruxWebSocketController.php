<?php


namespace Etlok\Crux\WebSockets\WebSockets\Controllers;
use App\Crux\Modules\WebSockets\Exceptions\BadWebSocketMethodException;
use App\Crux\Modules\WebSockets\Exceptions\InvalidDataWebSocketException;
use Ratchet\ConnectionInterface;

class CruxWebSocketController {

    public $handler = null;
    public $authenticable_entities;
    public $notifications = [];

    public function notify($message)
    {

        $this->notifications[] = $message;
    }
    public function __construct($handler)
    {
        $this->handler = $handler;
        $this->authenticable_entities = config('crux_websockets.authenticable_entities');
    }

    public function validate($data,$rules)
    {
        if($rules) {
            foreach ($rules as $field=>$rule) {
                $checks = explode("|", $rule);
                if($checks) {
                    foreach ($checks as $check) {
                        switch ($check) {
                            case 'required':
                                if(!isset($data[$field])) {
                                    throw new InvalidDataWebSocketException("Invalid Data",400);
                                }
                                break;
                        }
                    }
                }
            }
        }
    }

    public function can(ConnectionInterface $connection, $permission)
    {
        $entity = $this->authenticable_entities[$connection->app->entity];
        return $entity::hasPermissions($connection->app->entity_id, $permission);
    }

    public function __call(string $name, array $arguments)
    {
        if(method_exists($this->handler,$name)) {
            call_user_func_array([
                $this->handler,
                $name
            ],$arguments);
        } else {
            throw new BadWebSocketMethodException("Method Not Found X", 404);
        }

    }

    public function pullNotifications()
    {
        $notifications = $this->notifications;
        $this->notifications = [];
        return $notifications;
    }
}

