<?php

use Webman\GatewayWorker\Gateway;
use Webman\GatewayWorker\BusinessWorker;
use Webman\GatewayWorker\Register;

//文档地址  https://www.workerman.net/doc/gateway-worker/gateway.html
//文档地址  https://www.workerman.net/plugin/5
return [
    'gateway' => [
        'handler' => Gateway::class,
        'listen' => 'websocket://0.0.0.0:7272',
        'count' => 2,
        'reloadable' => false,
        'constructor' => [
            'config' => [
                'lanIp' => '127.0.0.1',
                'startPort' => 2300,
                'pingInterval' => 25,
                'pingNotResponseLimit' => 0, //其中pingNotResponseLimit = 0代表服务端允许客户端不发送心跳，服务端不会因为客户端长时间没发送数据而断开连接。如果pingNotResponseLimit = 1，则代表客户端必须定时发送数据给服务端，否则pingNotResponseLimit*pingInterval=55秒内没有任何数据发来则关闭对应连接，并触发onClose。
                'pingData' => '{"type":"ping"}',
                'registerAddress' => '127.0.0.1:1236',
                'onConnect' => function () {
                },
            ]
        ]
    ],
    'worker' => [
        'handler' => BusinessWorker::class,
        'count' => cpu_count() * 2,
        'constructor' => [
            'config' => [
                'eventHandler' => plugin\webman\gateway\Events::class,
                'name' => 'ChatBusinessWorker',
                'registerAddress' => '127.0.0.1:1236',
            ]
        ]
    ],
    'register' => [
        'handler' => Register::class,
        'listen' => 'text://127.0.0.1:1236',
        'count' => 1, // Must be 1
        'reloadable' => false,
        'constructor' => []
    ],
];
