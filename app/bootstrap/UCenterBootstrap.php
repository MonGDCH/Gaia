<?php

declare(strict_types=1);

namespace app\bootstrap;

use mon\env\Config;
use Workerman\Worker;
use mon\log\LoggerFactory;
use gaia\interfaces\BootstrapInterface;
use mon\ucenter\UCenter;

/**
 * Logger日志驱动初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UCenterBootstrap implements BootstrapInterface
{
    /**
     * 执行初始化业务
     *
     * @param Worker $worker
     * @return void
     */
    public static function start(Worker $worker): void
    {
        $config = [
            // 数据库断开自动重连
            'break_reconnect' => true,
            // 数据库配置
            'database' => Config::instance()->get('database.default')
        ];

        UCenter::instance()->setConfig($config)->init();
    }
}
