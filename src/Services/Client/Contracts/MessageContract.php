<?php

namespace Etlok\Crux\WebSockets\Services\Client\Contracts;

use Etlok\Crux\WebSockets\Services\Client\Exceptions\WebSocketException;

/**
 *
 * @author Arthur Kushman
 */
interface MessageContract
{
    /**
     * @param ConnectionContract $recv
     * @param $msg
     * @return mixed
     * @throws WebSocketException
     */
    public function onMessage(ConnectionContract $recv, $msg);
}
