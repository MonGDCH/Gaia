<?php

declare(strict_types=1);

namespace process\nat;

use Channel\Client;
use mon\env\Config;
use Workerman\Timer;
use Workerman\Worker;
use gaia\ProcessTrait;
use gaia\interfaces\ProcessInterface;
use Workerman\Connection\TcpConnection;

/**
 * 内网穿透外网访问服务进程
 *
 * Class server
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 2023-12-14
 */
class NatServer implements ProcessInterface
{
    use ProcessTrait;

    /**
     * worker实例
     *
     * @var Worker
     */
    private $_worker = null;

    /**
     * 配置信息
     *
     * @var array
     */
    private $config = ['debug' => true];

    /**
     * 定时盘判断客户端是否存在，不存在则断开链接
     *
     * @var integer
     */
    private $timerLimit = 5;

    /**
     * 客户端存活上报
     *
     * @var integer
     */
    private $timerLimitGap = 10;

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
        return Config::instance()->get('nat.server.net', []);
    }

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 加载配置
        $this->config = array_merge($this->config, Config::instance()->get('nat.app', []));
    }

    /**
     * 进程启动
     *
     * @param Worker $worker worker进程
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 链接channel通道服务
        Client::$onClose = function () {
            // channel服务断开
            $this->debugInfo('[wran] channel service connect close!');
        };
        Client::connect('127.0.0.1', $this->config['channel_port']);

        // 客户端是否在线
        $worker->isClientOnline = false;
        // 启动时间
        $worker->lastRegisterTime = time();

        // 定时判断客户端是否存在，不存在则断开现有外网链接
        Timer::add($this->timerLimit, function () use ($worker) {
            if (time() - $worker->lastRegisterTime > $this->timerLimitGap) {
                $worker->isClientOnline = false;
                foreach ($worker->connections as $conn) {
                    $conn->close();
                }
            }
        });

        // 客户端注册
        Client::on($this->getEvent('client_register'), function ($data) use ($worker) {
            // 校验token，完成注册客户端
            $worker->isClientOnline = true;
            $worker->lastRegisterTime = time();

            // 通过验证，下发透传代理配置，客户端通过下发的代理配置注册代理链接
            Client::publish($this->getEvent('server_register'), ['code' => 1]);
        });

        // 客户端收到链接代理链接成功后响应内容，输出给外网链接
        Client::on($this->getEvent('client_proxy_msg'), function ($data) use ($worker) {
            if (isset($worker->connections[$data['connection']['client_connection_id']])) {
                $worker->connections[$data['connection']['client_connection_id']]->send($data['data']);
            }
        });

        // 客户端代理断开链接
        Client::on($this->getEvent('client_proxy_close'), function ($data) use ($worker) {
            if (isset($worker->connections[$data['connection']['client_connection_id']])) {
                $worker->connections[$data['connection']['client_connection_id']]->close();
            }
        });
    }

    /**
     * 外网链接客户端
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onConnect(TcpConnection $connection)
    {
        if (!$connection->worker) {
            $this->debugInfo('外网链接客户端失败，Worker未初始化');
            return $connection->close();
        }
        if (!$connection->worker->isClientOnline) {
            $this->debugInfo('外网链接客户端失败，内网客户端未上线');
            return $connection->close();
        }

        $data['connection'] = [
            'ip' => $connection->getRemoteIp(),
            'port' => $connection->getRemotePort(),
            'client_connection_id' => $connection->id
        ];

        Client::publish($this->getEvent('out_net_connect'), $data);
    }

    /**
     * 服务端收到外网请求内容
     *
     * @param TcpConnection $connection
     * @param string $data
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        $message_data['connection'] = [
            'ip' => $connection->getRemoteIp(),
            'port' => $connection->getRemotePort(),
            'client_connection_id' => $connection->id
        ];
        $message_data['data'] = $data;

        Client::publish($this->getEvent('out_net_msg'), $message_data);
    }

    /**
     * 外网链接断开
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onClose(TcpConnection $connection)
    {
        $data['connection'] = [
            'ip' => $connection->getRemoteIp(),
            'port' => $connection->getRemotePort(),
            'client_connection_id' => $connection->id
        ];

        Client::publish($this->getEvent('out_net_close'), $data);
    }

    /**
     * 加密处理事件名，防止错误的监听
     *
     * @param string $event 事件名
     * @return string
     */
    private function getEvent(string $event): string
    {
        $name = $this->config['event'][$event] ?? '';
        return sha1($name . $this->config['token']);
    }

    /**
     * 调试输出
     *
     * @param string $msg
     * @return void
     */
    private function debugInfo(string $msg)
    {
        if ($this->config['debug']) {
            echo '[nat] ' . $msg . PHP_EOL;
        }
    }
}
