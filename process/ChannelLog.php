<?php

declare(strict_types=1);

namespace process;

use gaia\Process;
use Channel\Client;
use mon\env\Config;
use Workerman\Worker;
use app\libs\LogFactory;

/**
 * workermn\channel 进程通信服务队列监听，异步写入日志
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ChannelLog extends Process
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        'count' => 2
    ];

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker)
    {
        // 注册日志工厂
        $config = Config::instance()->get('log', []);
        LogFactory::instance()->loadChannel($config);
        // 监听记录日志
        Client::connect();
        Client::watch('log', [$this, 'log']);
    }

    /**
     * 记录日志
     *
     * @param array $log  日志内容
     * @return void
     */
    public function log(array $log)
    {
        $channel = $log['channel'] ?? 'error';
        if (!LogFactory::instance()->hasChannel($channel)) {
            return LogFactory::instance()->channel('error')->error('Log channel faild! log => ' . var_export($log, true));
        }

        $level = $log['level'];
        $message = $log['message'];
        return LogFactory::instance()->channel($channel)->log($level, $message);
    }
}
