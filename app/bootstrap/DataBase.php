<?php

declare(strict_types=1);

namespace app\bootstrap;

use mon\orm\Db;
use mon\env\Config;
use Workerman\Timer;
use Workerman\Worker;
use gaia\interfaces\Bootstrap;

/**
 * mon-orm初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class DataBase implements Bootstrap
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
        $config = Config::instance()->get('database', []);
        Db::setConfig($config);
        // 打开长链接
        Db::reconnect(true);
        // 每分钟轮训查询一次，确保不断开
        if ($worker) {
            Timer::add(55, function () use ($config) {
                foreach ($config as $key => $value) {
                    Db::connect($key)->query('SELECT 1');
                }
            });
        }
    }
}
