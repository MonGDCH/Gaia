<?php

declare(strict_types=1);

namespace process\nat;

use mon\env\Config;
use Channel\Server;
use Workerman\Worker;
use gaia\ProcessTrait;
use gaia\interfaces\ProcessInterface;
use Workerman\Connection\TcpConnection;

/**
 * 内网穿透进程通信服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1   2023-04-13
 */
class Channel extends Server implements ProcessInterface
{
    use ProcessTrait;

    /**
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool
    {
        return Config::instance()->get('nat.server.enable', false);
    }

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return Config::instance()->get('nat.server.channel', []);
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
        $worker->channels = [];
        $this->_worker = $worker;
    }

    /**
     * 客户端链接
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onConnect(TcpConnection $connection)
    {
        // TODO 可以通过IP白名单鉴权断开链接
        // dd($connection->getRemoteIp());
    }
}
