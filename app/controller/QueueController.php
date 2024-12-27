<?php

namespace app\controller;

use app\model\QueueNumber;
use app\model\Window;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use GatewayWorker\Lib\Gateway;
use support\Log;
use support\Request;
use support\Response;

class QueueController
{
    /**
     * 显示大屏
     */
    public function display(Request $request)
    {

        // 获取正在叫号的号码
        $current = QueueNumber::where('status', QueueNumber::STATUS_CALLING)
            ->whereDate('created_at', date('Y-m-d'))
            ->with('window')
            ->orderBy('updated_at', 'desc')
            ->get();


        // 获取等待叫号的号码
        $waiting = QueueNumber::where('status', QueueNumber::STATUS_WAITING)
            ->where('call_count', 0)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();


        // 获取过号的号码
        $passed = QueueNumber::where('status', QueueNumber::STATUS_PASSED)
            ->orderBy('updated_at', 'desc')
            ->whereDate('created_at', date('Y-m-d'))
            ->limit(10)
            ->get();


        return view('queue/display', [
            'current' => $current,
            'waiting' => $waiting,
            'passed' => $passed
        ]);
    }

    /**
     * 管理界面
     */
    public function admin(Request $request)
    {
        $windowId = $request->get('window');
        $page = $request->get('page', 1);
        $perPage = (int)$request->get('per_page', 10);

        // 限制每页显示数量的范围
        $perPage = in_array($perPage, [10, 20, 50]) ? $perPage : 10;

        $query = QueueNumber::whereIn('status', [
            QueueNumber::STATUS_CALLING,
            QueueNumber::STATUS_WAITING,
            QueueNumber::STATUS_PASSED
        ])
            ->when($windowId, function ($query) use ($windowId) {
                $query->whereIn('window_id', [0, $windowId]);
            })
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('created_at', 'asc');

        $waiting = $query->paginate($perPage, ['*'], 'page', $page);
        $windows = Window::where('status', Window::STATUS_ENABLED)
            ->orderBy('id', 'asc')
            ->get();

        return view('queue/admin', [
            'waiting' => $waiting,
            'windows' => $windows,
            'currentWindow' => $windowId,
            'perPage' => $perPage
        ]);
    }

    /**
     * 取号页面
     */
    public function takePage(Request $request)
    {
        //是否重新取号
        $renumbering = $request->get('renumbering');

        if ($renumbering) {
            $request->session()->delete('mobile');
        }

        //获取session中的手机号码
        $mobile = $request->session()->get('mobile');

        //如果存在手机号码
        if ($mobile) {
            $queue = QueueNumber::where('mobile', $mobile)
                ->whereDate('created_at', date('Y-m-d'))
                ->whereIn('status', [QueueNumber::STATUS_WAITING, QueueNumber::STATUS_PASSED, QueueNumber::STATUS_CALLING])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($queue) {
                return redirect('/queue/status/' . $queue->number);
            }
        }

        return view('queue/take');
    }

    /**
     * 获取当前叫号信息
     */
    public function current(): Response
    {
        $current = QueueNumber::with('window')
            ->where('status', QueueNumber::STATUS_CALLING)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('updated_at', 'desc')
            ->first();

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $current ? [
                'number' => $current->number,
                'name' => hideNameCharacters($current->name),
                'window' => $current->window->name
            ] : null
        ]);
    }

    /**
     * 取号
     */
    public function take(Request $request): Response
    {
        $name = $request->post('name');
        $mobile = $request->post('mobile');

        if (!$name || strlen($name) > 50) {
            return json(['code' => 1, 'msg' => '请输入正确的姓名']);
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json(['code' => 1, 'msg' => '请输入正确的手机号']);
        }

        try {

            $result = QueueNumber::generateNumber($name, $mobile);

            Gateway::$registerAddress = '127.0.0.1:1236';

            $message = [
                'type' => 'status_change',
                'data' => [
                    'name' => hideNameCharacters($name),
                    'number' => $result['number'],
                    'status' => QueueNumber::STATUS_WAITING
                ]
            ];

            //发送显示屏消息
            Gateway::sendToUid('display', json_encode($message));

            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $result['number'],
                    'name' => $name,
                    'mobile' => $mobile,
                    'call_count' => 0,
                    'window_id' => 0,
                    'window_name' => '未分配',
                    'status' => QueueNumber::STATUS_WAITING
                ]
            ];

            //发送窗口管理界面消息
            Gateway::sendToUid('window', json_encode($message));

            return json(['code' => 0, 'msg' => 'success', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 叫号
     */
    public function call(Request $request): Response
    {
        $number = $request->post('number');
        $window = $request->post('window');

        if (!$window) {
            return json(['code' => 1, 'msg' => '请选择窗口']);
        }

        // 检查窗口是否有效
        $windowExists = Window::where('id', $window)
            ->where('status', Window::STATUS_ENABLED)
            ->first();

        if (!$windowExists) {
            return json(['code' => 1, 'msg' => '无效的窗口号']);
        }

        // 自动将该窗口之前叫号的号码标记为过号
        try {
            autoPassOverCalledNumbers($window);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        $queue = QueueNumber::where('number', $number)
            ->orWhere('mobile', $number)
            ->whereIn('status', [QueueNumber::STATUS_WAITING, QueueNumber::STATUS_PASSED])
            ->whereDate('created_at', date('Y-m-d'))
            ->first();

        if (!$queue) {
            return json(['code' => 1, 'msg' => '号码不存在或状态错误']);
        }

        $queue->status = QueueNumber::STATUS_CALLING;
        $queue->window_id = $window;
        $queue->call_count++;
        $queue->save();


        try {
            $message = [
                'type' => 'call_number',
                'data' => [
                    'number' => $queue->number,
                    'name' => hideNameCharacters($queue->name),
                    'call_count' => $queue->call_count,
                    'window_id' => $window,
                    'window_name' => $windowExists->name,
                    'status' => QueueNumber::STATUS_CALLING
                ]
            ];
            // 发送给显示屏
            Gateway::$registerAddress = '127.0.0.1:1236';
            Gateway::sendToUid('display', json_encode($message));

            $message = [
                'type' => 'call_number',
                'data' => [
                    'number' => $queue->number,
                    'name' => $queue->name,
                    'mobile' => $queue->mobile,
                    'call_count' => $queue->call_count,
                    'window_id' => $window,
                    'window_name' => $windowExists->name,
                    'status' => QueueNumber::STATUS_CALLING
                ]
            ];
            // 发送给窗口管理界面
            Gateway::sendToUid('window', json_encode($message));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * 完成办理
     */
    public function complete(Request $request): Response
    {
        $number = $request->post('number');

        $queue = QueueNumber::where('number', $number)
            ->whereIn('status', [QueueNumber::STATUS_CALLING, QueueNumber::STATUS_WAITING, QueueNumber::STATUS_PASSED])
            ->whereDate('created_at', date('Y-m-d'))
            ->first();

        if (!$queue) {
            return json(['code' => 1, 'msg' => '号码不存在或状态错误']);
        }

        $queue->status = QueueNumber::STATUS_COMPLETED;
        $queue->save();


        try {

            Gateway::$registerAddress = '127.0.0.1:1236';
            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => hideNameCharacters($queue->name),
                    'status' => QueueNumber::STATUS_COMPLETED
                ]
            ];
            Gateway::sendToUid('display', json_encode($message));

            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => $queue->name,
                    'mobile' => $queue->mobile,
                    'status' => QueueNumber::STATUS_COMPLETED
                ]
            ];
            Gateway::sendToUid('window', json_encode($message));
        } catch (\Exception $e) {
            \support\Log::error($e->getMessage());
        }

        return json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * 标记过号
     */
    public function pass(Request $request): Response
    {
        $number = $request->post('number');

        $queue = QueueNumber::with('window')
            ->where('number', $number)
            ->where('status', QueueNumber::STATUS_CALLING)
            ->whereDate('created_at', date('Y-m-d'))
            ->first();

        if (!$queue) {
            return json(['code' => 1, 'msg' => '号码不存在或状态错误']);
        }

        $queue->status = QueueNumber::STATUS_PASSED;
        $queue->save();


        try {
            Gateway::$registerAddress = '127.0.0.1:1236';
            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => hideNameCharacters($queue->name),
                    'status' => QueueNumber::STATUS_PASSED
                ]
            ];
            Gateway::sendToUid('display', json_encode($message));


            // 使用Gateway发送状态更新通知
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => $queue->name,
                    'mobile' => $queue->mobile,
                    'call_count' => $queue->call_count,
                    'window_id' => $queue->window_id,
                    'window_name' => optional($queue->window)->name,
                    'status' => QueueNumber::STATUS_PASSED
                ]
            ];
            Gateway::sendToUid('window', json_encode($message));

        } catch (\Exception $e) {
            \support\Log::error($e->getMessage());
        }

        return json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * 取消号码
     */
    public function cancel(Request $request): Response
    {
        $number = $request->post('number');

        $queue = QueueNumber::with('window')
            ->where('number', $number)
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('status', [QueueNumber::STATUS_CALLING, QueueNumber::STATUS_WAITING, QueueNumber::STATUS_PASSED])
            ->first();

        if (!$queue) {
            return json(['code' => 1, 'msg' => '号码不存在或状态错误']);
        }

        $queue->status = QueueNumber::STATUS_CANCELLED;
        $queue->save();


        try {
            Gateway::$registerAddress = '127.0.0.1:1236';
            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => hideNameCharacters($queue->name),
                    'status' => QueueNumber::STATUS_CANCELLED
                ]
            ];
            Gateway::sendToUid('display', json_encode($message));

            $message = [
                'type' => 'status_change',
                'data' => [
                    'number' => $number,
                    'name' => $queue->name,
                    'mobile' => $queue->mobile,
                    'call_count' => $queue->call_count,
                    'window_id' => $queue->window_id,
                    'window_name' => optional($queue->window)->name,
                    'status' => QueueNumber::STATUS_CANCELLED
                ]
            ];
            Gateway::sendToUid('window', json_encode($message));
        } catch (\Exception $e) {
            \support\Log::error($e->getMessage());
        }

        return json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * 生成二维码
     */
    public function qrcode(Request $request): Response
    {
        $url = $request->get('url', '');

        $writer = new PngWriter();

        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $writer->write($qrCode);

        return response($result->getString())
            ->withHeader('Content-Type', 'image/png');
    }

    /**
     * 查询号码状态
     */
    public function status(Request $request, $number)
    {
        $queue = QueueNumber::with('window')
            ->where('number', $number)
            ->whereDate('created_at', date('Y-m-d'))
            ->first();

        //获取待叫号状态时 前面还有多少人数
        if ($queue->status == QueueNumber::STATUS_WAITING) {
            $queue->waiting = QueueNumber::where('status', QueueNumber::STATUS_WAITING)
                ->where('id', '<', $queue->id)
                ->whereDate('created_at', date('Y-m-d'))
                ->count();
        }

        $badgeClass = $this->getStatusBadgeClass($queue->status);

        return view('queue/status', ['queue' => $queue, 'badgeClass' => $badgeClass]);
    }

    /**
     * 获取状态对应的徽章样式类
     */
    private function getStatusBadgeClass($status): string
    {
        return match ($status) {
            QueueNumber::STATUS_WAITING => 'warning',
            QueueNumber::STATUS_CANCELLED => 'secondary',
            QueueNumber::STATUS_PASSED => 'danger',
            QueueNumber::STATUS_COMPLETED => 'success',
            QueueNumber::STATUS_CALLING => 'primary',
            default => 'info',
        };
    }
} 