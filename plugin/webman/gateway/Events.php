<?php

namespace plugin\webman\gateway;

use Exception;
use GatewayWorker\Lib\Gateway;

/**
 * https://www.workerman.net/doc/gateway-worker/session.html
 * onWorkerStart businessWorker进程启动事件（一般用不到）
 * onConnect 连接事件(比较少用到)
 * onWebSocketConnect 当客户端连接上gateway完成websocket握手时触发的回调函数。 注意：此回调只有gateway为websocket协议并且gateway没有设置onWebSocketConnect时才有效。
 * onMessage 消息事件(必用)
 * onClose 连接断开事件(比较常用到)
 * onWorkerStop businessWorker进程退出事件（几乎用不到）
 */
class Events
{
    /**
     *
     * 可以在这里为每一个businessWorker进程做一些全局初始化工作，
     * 例如设置定时器，初始化redis等连接等。
     * @param $worker
     * @return void
     */
    public static function onWorkerStart($worker): void
    {

    }

    /**
     * client_id固定为20个字符的字符串，用来全局标记一个socket连接，每个客户端连接都会被分配一个全局唯一的client_id。
     * 如果client_id对应的客户端连接断开了，那么这个client_id也就失效了。当这个客户端再次连接到Gateway时，将会获得一个新的client_id。也就是说client_id和客户端的socket连接生命周期是一致的。
     * client_id一旦被使用过，将不会被再次使用，也就是说client_id是不会重复的，即使分布式部署也不会重复。
     * $client_id是服务端自动生成的并且无法自定义。
     * 如果开发者有自己的id系统，可以用过Gateway::bindUid($client_id, $uid)把自己系统的id与client_id绑定，
     * 绑定后就可以通过Gateway::sendToUid($uid)发送数据，通
     * 通过Gateway::isUidOnline($uid)用户是否在线了。
     * onConnect事件仅仅代表客户端与gateway完成了TCP三次握手，这时客户端还没有发来任何数据，此时除了通过$_SERVER['REMOTE_ADDR']获得对方ip，
     * 没有其他可以鉴别客户端的数据或者信息，
     * 所以在onConnect事件里无法确认对方是谁。
     * 要想知道对方是谁，需要客户端发送鉴权数据，
     * 例如某个token或者用户名密码之类，在onMesssge里做鉴权。
     * @param $client_id
     * @return void
     * @throws Exception
     */
    public static function onConnect($client_id): void
    {
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'connected',
            'client_id' => $client_id
        ]));
    }

    /**
     * @param $client_id
     * client_id固定为20个字符的字符串，用来全局标记一个socket连接，每个客户端连接都会被分配一个全局唯一的client_id。
     * @param $data
     * websocket握手时的http头数据，包含get、server等变量
     * var ws = new WebSocket('ws://127.0.0.1:7272/?token=kjxdvjkasfh');
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data): void
    {
//        if (!isset($data['get']['token'])) {
//            Gateway::closeClient($client_id);
//        }
    }


    public static function onMessage($client_id, $message)
    {
        try {
            $data = json_decode($message, true);
            if (!$data || !isset($data['type'])) {
                return;
            }

            switch ($data['type']) {
                case 'bind_display':
                    self::handleBindDisplay($client_id);
                    break;

                case 'bind_window':
                    self::handleBindWindow($client_id);
                    break;

                case 'heartbeat':
                    Gateway::sendToClient($client_id, json_encode([
                        'type' => 'heartbeat',
                        'time' => time()
                    ]));
                    break;
            }
        } catch (\Exception $e) {
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * 当用户断开连接时触发
     * 注意：onClose回调里无法使用Gateway::getSession()来获得当前用户的session数据，但是仍然可以使用$_SESSION变量获得。
     *
     * 注意：onClose回调里无法使用Gateway::getUidByClientId()接口来获得uid，
     * 解决办法是在Gateway::bindUid()时记录一个$_SESSION['uid']，
     * onClose的时候用$_SESSION['uid']来获得uid。
     *
     * 注意：断网断电等极端情况可能无法及时触发onClose回调，
     * 因为这种情况客户端来不及给服务端发送断开连接的包(fin包)，
     * 服务端就无法得知连接已经断开。
     * 检测这种极端情况需要心跳检测，并
     * 且必须设置$gateway->pingNotResponseLimit>0。
     * 这种断网断电的极端情况onClose将被延迟触发，延迟时间为小于$gateway->pingInterval*$gateway->pingNotResponseLimit秒，
     * 如果$gateway->pingInterval 和 $gateway->pingNotResponseLimit 中任何一个为0，
     * 则可能会无限延迟。
     * @throws Exception
     */
    public static function onClose($client_id): void
    {
        // 可以在这里清理一些数据
        Gateway::sendToAll(json_encode([
            'type' => 'client_closed',
            'client_id' => $client_id
        ]));
    }


    /**
     * 处理显示屏绑定
     */
    private static function handleBindDisplay($client_id): void
    {
        Gateway::bindUid($client_id, 'display');
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'bind_success',
            'display' => true
        ]));
    }

    /**
     * 处理窗口绑定
     */
    private static function handleBindWindow($client_id): void
    {
        Gateway::bindUid($client_id, 'window');
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'bind_success',
            'display' => true
        ]));
    }
}
