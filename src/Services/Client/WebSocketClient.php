<?php

namespace Etlok\Crux\WebSockets\Services\Client;

use Etlok\Crux\WebSockets\Services\Client\Components\ClientConfig;
use Etlok\Crux\WebSockets\Services\Client\Components\WscMain;

class WebSocketClient extends WscMain
{
    /**
     * Sets parameters for Web Socket Client intercommunication
     *
     * @param string $url string representation of a socket utf, ex.: tcp://www.example.com:8000 or udp://example.com:13
     * @param ClientConfig $config Client configuration settings e.g.: connection - timeout, ssl options, fragment message size to send etc.
     * @throws \InvalidArgumentException
     * @throws \Etlok\Crux\WebSockets\Services\Client\Exceptions\BadUriException
     * @throws \Etlok\Crux\WebSockets\Services\Client\Exceptions\ConnectionException
     * @throws \Exception
     */
    public function __construct(string $url, ClientConfig $config)
    {
        $this->socketUrl = $url;
        $this->connect($config);
    }
}
