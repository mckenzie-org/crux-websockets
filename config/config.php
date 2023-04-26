<?php

use Etlok\Crux\WebSockets\Services\MessageHandler;

return [
    'authenticable_entities'=>[

    ],
    'allowed_origins'=>[

    ],
    'capacity'=>1000000,
    'app_id'=>env('CRUX_WEBSOCKETS_APP_ID','crux'),
    'server_id'=>env('CRUX_WEBSOCKET_SERVER_ID',\Illuminate\Support\Str::uuid()->toString()),
    'service'=>\Etlok\Crux\WebSockets\Services\WebsocketService::class,
    'handlers'=>[
        'default'=>[
            'routes'=>'client.php',
            'type'=>MessageHandler::class
        ],
    ],
    'web'=>[
        'prefix'=>'',
        'middleware'=>[]
    ],
    'replication'=>[
        'mode'=>'redis',
        'redis'=>[
            'channel_manager'=>\Etlok\Crux\WebSockets\ChannelManagers\RedisChannelManager::class,
            'connection'=>'default'
        ]
    ],
    'ssl'=>[
        'local_cert'=>env('CRUX_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'capath' => env('CRUX_WEBSOCKETS_SSL_CA', null),
        'local_pk' => env('CRUX_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('CRUX_WEBSOCKETS_SSL_PASSPHRASE', null),
        'verify_peer' => env('APP_ENV') === 'production',
        'allow_self_signed' => env('APP_ENV') !== 'production',
    ],
    'max_request_size_in_kb'=>250
];