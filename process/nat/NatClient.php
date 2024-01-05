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
use Workerman\Connection\AsyncTcpConnection;

/**
 * 内网穿透本地客户端进程
 *
 * Class server
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 2023-12-14
 */
class NatClient implements ProcessInterface
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
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool
    {
        // return false;
        return Config::instance()->get('nat.client.enable', false);
    }

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return Config::instance()->get('nat.client.config', []);
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
        Client::$onClose = function () {
            // channel服务断开
            $this->debugInfo('[wran] channel service connect close!');
        };
        // 链接服务端通道
        Client::$onClose = function () {
            // channel服务断开
            $this->debugInfo('[wran] channel service connect close!');
        };
        Client::connect($this->config['channel_host'], $this->config['channel_port']);

        // 客户端注册
        Client::publish($this->getEvent('client_register'), ['token' => $this->config['token']]);
        Timer::add($this->timerLimit, function () use ($worker) {
            Client::publish($this->getEvent('client_register'), ['token' => $this->config['token']]);
        });

        // 客户端注册成功
        Client::on($this->getEvent('server_register'), function ($data) use ($worker) {
            // $worker->isOnline = true;
            // dd($data);
        });

        // 服务端收到外网连接请求
        Client::on($this->getEvent('out_net_connect'), function ($data) use ($worker) {
            $this->debugInfo('[info] 收到外网链接!');

            // 链接本地服务
            $proxy_address = "tcp://{$this->config['proxy_host']}:{$this->config['proxy_port']}";
            $local_conn = new AsyncTcpConnection($proxy_address);
            $local_conn->onConnect = function ($connection) {
                $this->debugInfo("[info] 与本地服务连接成功");
                // $connect_data['connection'] = [
                //     'ip' => $connection->getRemoteIp(),
                //     'port' => $connection->getRemotePort(),
                //     'client_connection_id' => $data['connection']['client_connection_id']
                // ];
                // Client::publish('send_connect' . $this->config['token'], $connect_data);
            };

            // 服务端收到外部请求内容，客户端向代理服务发送请求，代理服务响应内容
            $local_conn->onMessage = function ($connection, $queryData) use ($data) {
                $message_data['data'] = $queryData;
                $message_data['connection'] = [
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'client_connection_id' => $data['connection']['client_connection_id']
                ];

                Client::publish($this->getEvent('client_proxy_msg'), $message_data);
            };

            // 代理断开链接
            $local_conn->onClose = function ($connection) use ($data) {
                $this->debugInfo("[info] 与本地服务连接断开");
                $close_data['connection'] = [
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'client_connection_id' => $data['connection']['client_connection_id']
                ];

                Client::publish($this->getEvent('client_proxy_close'), $close_data);
            };

            $this->debugInfo("[info] 与本地服务发起连接");
            $local_conn->connect();
            // 保存服务
            $worker->connections[$data['connection']['client_connection_id']] = $local_conn;
        });

        // 服务端收到外部请求内容，客户端向代理服务发送请求
        Client::on($this->getEvent('out_net_msg'), function ($data) use ($worker) {
            $worker->connections[$data['connection']['client_connection_id']]->send($data['data']);
        });

        // 服务端外网请求断开，客户端代理链接也断开
        Client::on($this->getEvent('out_net_close'), function ($data) use ($worker) {
            if (
                isset($worker->connections[$data['connection']['client_connection_id']]) &&
                $worker->connections[$data['connection']['client_connection_id']] != null
            ) {
                $worker->connections[$data['connection']['client_connection_id']]->close();
                unset($worker->connections[$data['connection']['client_connection_id']]);
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
        // dd($connection->protocol);
    }

    /**
     * 服务端收到外网请求内容
     *
     * @param TcpConnection $connection
     * @param mixed $data
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data)
    {
    }

    /**
     * 外网链接断开
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onClose(TcpConnection $connection)
    {
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
