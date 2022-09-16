<?php

declare(strict_types=1);

namespace app\bootstrap;

use mon\env\Config;
use Workerman\Worker;
use mon\log\LoggerFactory;
use gaia\interfaces\BootstrapInterface;

/**
 * Logger日志驱动初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LoggerBootstrap implements BootstrapInterface
{
    /**
     * 执行初始化业务
     *
     * @param Worker $worker
     * @return void
     */
    public static function start(Worker $worker): void
    {
        // 定义配置
        $config = Config::instance()->get('log', []);
        LoggerFactory::instance()->registerChannel($config);
    }
}
