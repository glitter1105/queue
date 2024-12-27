<?php

namespace app\model;

use support\Model;

class QueueNumber extends Model
{
    protected $table = 'queue_numbers';

    // 状态常量定义
    const STATUS_WAITING = 0;    // 待叫号
    const STATUS_CANCELLED = 1;  // 已取消
    const STATUS_PASSED = 2;     // 已过号
    const STATUS_COMPLETED = 3;  // 已完成
    const STATUS_CALLING = 4;     // 叫号中

    protected $fillable = [
        'number',        // 排队号码
        'name',          // 姓名
        'mobile',        // 手机号
        'status',        // 状态
        'window_id',        // 窗口号
        'call_count',    // 叫号次数
        'qrcode_url'     // 二维码链接
    ];

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => '待叫号',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_PASSED => '已过号',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CALLING => '叫号中',
            default => '未知状态',
        };
    }

    /**
     * 生成新号码
     * @param string $name
     * @param string $mobile
     * @return array
     */
    public static function generateNumber(string $name, string $mobile): array
    {
        // 检查是否存在未完成的号码
        $existingNumber = self::where('mobile', $mobile)
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('status', [self::STATUS_WAITING])
            ->first();

        if ($existingNumber) {
            // 将已存在的号码标记为已取消
            $existingNumber->status = self::STATUS_CANCELLED;
            $existingNumber->save();
        }

        // 生成新号码
        $date = date('Ymd');
        $lastNumber = self::whereDate('created_at', $date)
            ->orderBy('number', 'desc')
            ->value('number');

        if (!$lastNumber) {
            $newNumber = '001';
        } else {
            $sequence = (int)$lastNumber + 1;
            $newNumber = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        }

        // 生成二维码链接
        $qrcodeUrl = '/queue/status/' . $newNumber;


        //获取正在等待叫号的人数
        $waitingCount = self::where('status', self::STATUS_WAITING)
            ->whereDate('created_at', date('Y-m-d'))
            ->count();

        // 创建新记录
        self::create([
            'name' => $name,
            'number' => $newNumber,
            'mobile' => $mobile,
            'status' => self::STATUS_WAITING,
            'call_count' => 0,
            'qrcode_url' => $qrcodeUrl
        ]);


        return [
            'number' => $newNumber,
            'qrcode_url' => $qrcodeUrl,
            'cancelled_number' => $existingNumber?->number,
            'waiting_count' => $waitingCount
        ];
    }

    /**
     * 重新排队（后移5位）
     * @return bool
     */
    public function requeue(): bool
    {
        $waitingQueue = self::whereIn('status', [self::STATUS_WAITING])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($waitingQueue->isEmpty()) {
            return true;
        }

        $currentPosition = $waitingQueue->search(function ($item) {
            return $item->id === $this->id;
        });

        if ($currentPosition === false) {
            if ($waitingQueue->isNotEmpty()) {
                $lastItem = $waitingQueue->last();
                $this->created_at = date('Y-m-d H:i:s', strtotime($lastItem->created_at) + 1);
                $this->save();
            }
            return true;
        }

        $newPosition = min($currentPosition + 5, $waitingQueue->count() - 1);
        $targetItem = $waitingQueue[$newPosition];

        $this->created_at = date('Y-m-d H:i:s', strtotime($targetItem->created_at) + 1);
        $this->save();

        return true;
    }



    public function window()
    {
        return $this->belongsTo(Window::class, 'window_id', 'id');
    }
}