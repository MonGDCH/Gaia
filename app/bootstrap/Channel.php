<?php

declare(strict_types=1);

namespace app\bootstrap;

use Channel\Client;
use Workerman\Worker;
use gaia\interfaces\Bootstrap;

/**
 * channel初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Channel implements Bootstrap
{
    /**
     * 执行初始化业务
     *
     * @param Worker $worker
     * @return void
     */
    public static function start(Worker $worker)
    {
        // 定义配置
        Client::connect();
    }
}
