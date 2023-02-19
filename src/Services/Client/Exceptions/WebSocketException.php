<?php

namespace Etlok\Crux\WebSockets\Services\Client\Exceptions;

class WebSocketException extends \Exception
{
    public function printStack()
    {
        echo $this->getFile() . ' ' . $this->getLine() . ' ' . $this->getMessage() . PHP_EOL;
        echo $this->getTraceAsString();
    }
}
