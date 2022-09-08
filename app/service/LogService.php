<?php

declare(strict_types=1);

namespace app\service;

use Channel\Client;
use Psr\Log\LogLevel;
use mon\util\Instance;

/**
 * 日志服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LogService
{
    use Instance;

    /**
     * 构造方法
     */
    protected function __construct()
    {
        Client::connect();
    }

    /**
     * 发送日志
     *
     * @param string $level     日志级别
     * @param string $message   日志内容
     * @param string $channel   日志通道
     * @return void
     */
    public function send(string $level, string $message, string $channel = '', bool $trace = false, int $layer =  1)
    {
        $channel = $channel ?: 'default';
        // 日志追踪
        if ($trace) {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $layer);
            $infoLayer = $layer - 1;
            $file = $traceInfo[$infoLayer]['file'];
            $line = $traceInfo[$infoLayer]['line'];
            $message = "[{$file} => {$line}] " . $message;
        }

        $log = [
            'channel'   => $channel,
            'level'     => $level,
            'message'   => $message,
        ];
        Client::enqueue('log', $log);
    }

    /**
     * 系统无法使用错误级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return Log
     */
    public function emergency(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::EMERGENCY, $message, $channel, $trace, $layer);
    }

    /**
     * 必须立即采取行动错误级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function alert(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::ALERT, $message, $channel, $trace, $layer);
    }

    /**
     * 临界错误级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function critical(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::CRITICAL, $message, $channel, $trace, $layer);
    }

    /**
     * 运行时错误级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function error(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::ERROR, $message, $channel, $trace, $layer);
    }

    /**
     * 警告级别错误信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function warning(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::WARNING, $message, $channel, $trace, $layer);
    }

    /**
     * 事件级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function notice(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::NOTICE, $message, $channel, $trace, $layer);
    }

    /**
     * 一般级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function info(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::INFO, $message, $channel, $trace, $layer);
    }

    /**
     * 调试级别信息
     *
     * @param string $message   日志信息
     * @param string $channel   日志通道
     * @return void
     */
    public function debug(string $message, string $channel = '', bool $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->send(LogLevel::DEBUG, $message, $channel, $trace, $layer);
    }
}
