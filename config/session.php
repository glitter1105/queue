<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Session\FileSessionHandler;
use Webman\Session\RedisSessionHandler;
use Webman\Session\RedisClusterSessionHandler;

return [

    // handler为FileSessionHandler::class时值为file，
    // handler为RedisSessionHandler::class时值为redis
    // handler为RedisClusterSessionHandler::class时值为redis_cluster 既redis集群
    'type' => 'file', // or redis or redis_cluster

     // FileSessionHandler::class 或者 RedisSessionHandler::class 或者 RedisClusterSessionHandler::class

    'handler' => FileSessionHandler::class,

    'config' => [
        'file' => [
            'save_path' => runtime_path() . '/sessions',
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => '',
            'timeout' => 2,
            'database' => '',
            'prefix' => 'redis_session_',
        ],
        'redis_cluster' => [
            'host' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7001'],
            'timeout' => 2,
            'auth' => '',
            'prefix' => 'redis_session_',
        ]
    ],

    'session_name' => 'PHPSID', //// 存储session_id的cookie名
    
    'auto_update_timestamp' => false, // 是否自动刷新session，默认关闭

    'lifetime' => 24*60*60, // session过期时间

    'cookie_lifetime' => 365*24*60*60, // 存储session_id的cookie过期时间

    'cookie_path' => '/', // 存储session_id的cookie路径

    'domain' => '',  // 存储session_id的cookie域名
    
    'http_only' => true,   // 是否开启httpOnly，默认开启

    'secure' => false, // 仅在https下开启session，默认关闭
    
    'same_site' => '',   // 用于防止CSRF攻击和用户追踪，可选值strict/lax/none

    'gc_probability' => [1, 1000],  // 回收session的几率

];
