<?php

use Etlok\Crux\WebSockets\Services\MessageHandler;

return [
    'authenticable_entities'=>[

    ],
    'handlers'=>[
        'default'=>[
            'routes'=>'client.php',
            'type'=>MessageHandler::class
        ],
    ],
    'web'=>[
        'prefix'=>'',
        'middleware'=>[]
    ]
];