<?php
/**
 * Here is your custom functions.
 */

use app\model\QueueNumber;
use GatewayWorker\Lib\Gateway;

/**
 * 自动将指定窗口叫号超过1次的号码标记为过号
 * @param int $window 窗口号
 * @return void
 * @throws Exception
 */
function autoPassOverCalledNumbers(int $window): void
{
    $overCalledNumbers = QueueNumber::with('window')
        ->where('window_id', $window)
        ->whereIn('status', [QueueNumber::STATUS_WAITING, QueueNumber::STATUS_CALLING])
        ->where('call_count', '>', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->get();

    if ($overCalledNumbers->isEmpty()) {
        return;
    }

    foreach ($overCalledNumbers as $number) {
        $number->status = QueueNumber::STATUS_PASSED;
        $number->save();

        // 推送消息通知显示屏更新
        try {
            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number->number,
                    'name' => hideNameCharacters($number->name),
                    'status' => QueueNumber::STATUS_PASSED
                ]
            ];

            Gateway::sendToUid('display', json_encode($message));

            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number->number,
                    'name' => $number->name,
                    'mobile' => $number->mobile,
                    'call_count' => $number->call_count,
                    'window' => $number->window_id,
                    'window_name' => optional($number->window)->name,
                    'status' => QueueNumber::STATUS_PASSED
                ]
            ];
            Gateway::sendToUid('window', json_encode($message));
        } catch (\Webman\Push\PushException $e) {
            // 记录推送异常
            \support\Log::error($e->getMessage());
        }
    }
}


function hideNameCharacters(string $name): string
{
    $length = mb_strlen($name, 'UTF-8'); // 获取字符串长度，使用 mb_strlen 处理 UTF-8 字符

    if ($length <= 1) {
        return $name; // 名字只有一个字或者为空，不处理
    }

    $firstChar = mb_substr($name, 0, 1, 'UTF-8'); // 获取第一个字符
    $lastChar = mb_substr($name, $length - 1, 1, 'UTF-8'); // 获取最后一个字符

    if ($length == 2) {
        return $firstChar . "*"; // 名字只有两个字，隐藏第二个字
    }

    $hiddenChars = str_repeat('*', $length - 2); // 生成中间的 * 字符串
    return $firstChar . $hiddenChars . $lastChar; // 拼接最终的字符串
}

