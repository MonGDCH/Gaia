<?php

declare(strict_types=1);

namespace process;

use Channel\Server;
use Workerman\Worker;
use gaia\interfaces\ProcessInterface;

/**
 * workermn\channel 进程通信服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Channel extends Server implements ProcessInterface
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        // 监听协议断开
        'listen'    => 'frame://0.0.0.0:2206',
        // 进程数，必须是1
        'count'     =>  1,
    ];

    /**
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool
    {
        return true;
    }

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return static::$processConfig;
    }

    /**
     * 重载构造方法
     */
    public function __construct()
    {
    }

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker)
    {
        $this->_worker = $worker;
        $this->channels = [];
    }
}
