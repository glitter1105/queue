<?php

namespace app\common;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

class LogFormatter extends LineFormatter
{
    const ERROR = "\033[31m";
    const INFO = "\033[32m";
    const WARNING = "\033[33m";

    const END = "\033[0m";

    public function format(LogRecord|array $record): string
    {
        $color = match (strtolower($record['level_name'])) {
            strtolower(LogLevel::INFO) => self::INFO,
            strtolower(LogLevel::WARNING) => self::WARNING,
            strtolower(LogLevel::ERROR) => self::ERROR,
            default => '',
        };
        return $color . parent::format($record) . self::END;
    }
}