<?php

declare(strict_types=1);

namespace process;

use gaia\Process;
use mon\util\Log;
use Channel\Client;
use Workerman\Worker;

/**
 * workermn\channel 进程通信服务队列监听
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ChannelEnqueue extends Process
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
        $config = [
            'maxSize'   => 20480000,
            'logPath'   => RUNTIME_PATH . '/log',
            'rollNum'   => 3,
            'logName'   => '',
            'splitLine' => '',
            'save'      => true
        ];
        Log::instance($config);
        // 监听日志记录
        Client::watch('log', [$this, 'log']);
    }

    /**
     * 记录日志
     *
     * @param string $log  日志内容
     * @return void
     */
    public function log(string $log)
    {
        Log::instance()->debug($log, [], false);
    }
}
