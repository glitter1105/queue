<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Cache;

class QueueRateLimit implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if ($request->path() === '/queue/take' && $request->method() === 'POST') {
            $mobile = $request->post('mobile');
            $key = "queue_rate_limit:{$mobile}";
            
            // 检查是否在限制时间内
            if (Cache::get($key)) {
                return json([
                    'code' => 1,
                    'msg' => '取号太频繁，请稍后再试'
                ]);
            }
            
            // 设置限制时间（5分钟）
            Cache::set($key, 1, 300);
        }
        
        return $handler($request);
    }
} 