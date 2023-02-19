<?php

namespace Etlok\Crux\WebSockets\Services;

use Etlok\Crux\WebSockets\Services\Client\Components\ClientConfig;
use Etlok\Crux\WebSockets\Services\Client\WebSocketClient;

class WebsocketBroadcast {

    protected $auth = [];
    protected $client = null;
    protected $config = null;
    protected $url = null;

    public function __construct($authentication = ['auth'=>'','url'=>''], $config = null)
    {
        $this->config = $config??(new ClientConfig());
        $this->setUrl($authentication['url']);
        $this->setAuthentication($authentication['auth']);
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setAuthentication($auth)
    {
        $this->auth = $auth;
    }

    public function connect()
    {
        $this->client = new WebSocketClient($this->url, $this->config);
    }

    public function send($payload)
    {
        if($this->client === null) {
            $this->connect();
        }
        $this->client->send(json_encode(array_merge_recursive([
                                                            'auth'=>$this->auth,
                                                        ],$payload)));
    }

}
