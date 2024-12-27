<?php

namespace app\bootstrap;

use Webman\Bootstrap;
use Workerman\Timer;


/**
 *     ⠰⢷⢿⠄
 * ⠀⠀⠀⠀⠀⣼⣷⣄
 * ⠀⠀ ⣤⣿⣇⣿⣿⣧⣿⡄
 * ⢴⠾⠋⠀⠀⠻⣿⣷⣿⣿⡀
 *  🏀⠀⢀⣿⣿⡿⢿⠈⣿
 *    ⠀⢠⣿⡿⠁⠀⡊⠀⠙
 *     ⠀⢿⣿⠀ ⠀⠹⣿
 *     ⠀⠀⠹⣷⡀ ⠀⣿⡄
 *     ⠀ ⣀⣼⣿⠀ ⢈⣧
 */
class MemRepor implements Bootstrap
{
    public static function start($worker): void
    {
        // Is it console environment ?
        $is_console = !$worker;
        if ($is_console) {
            // If you do not want to execute this in console, just return.
            return;
        }

        // monitor进程不执行定时器
        if ($worker->name == 'monitor') {
            return;
        }

        // 每隔10秒执行一次
        Timer::add(10, function () {
            // 为了方便演示，这里使用输出代替上报过程
            echo memory_get_usage() . "\n";
        });


    }

}
