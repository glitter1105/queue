<?php

namespace app\model;

use support\Model;

class Window extends Model
{
    protected $table = 'windows';

    protected $fillable = [
        'name',          // 窗口名称
        'number',        // 窗口编号
        'description',   // 窗口描述
        'status',        // 窗口状态 1:启用 0:禁用
    ];

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_ENABLED => '启用',
            self::STATUS_DISABLED => '禁用',
            default => '未知状态',
        };
    }

    /**
     * 获取当前窗口的叫号记录
     */
    public function queueNumbers()
    {
        return $this->hasMany(QueueNumber::class, 'window_id', 'id');
    }

    /**
     * 获取今日该窗口处理的号码数量
     */
    public function getTodayProcessedCount(): int
    {
        return $this->queueNumbers()
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('status', [
                QueueNumber::STATUS_COMPLETED,
                QueueNumber::STATUS_PASSED
            ])
            ->count();
    }

    /**
     * 获取该窗口当前等待人数
     */
    public function getWaitingCount(): int
    {
        return $this->queueNumbers()
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', QueueNumber::STATUS_WAITING)
            ->count();
    }

    /**
     * 获取该窗口当前正在处理的号码
     */
    public function getCurrentNumber()
    {
        return $this->queueNumbers()
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', QueueNumber::STATUS_CALLING)
            ->first();
    }
} 