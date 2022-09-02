<?php

declare(strict_types=1);

namespace process;

use gaia\Process;
use mon\http\App;
use mon\env\Config;
use Workerman\Worker;
use mon\util\Container;
use mon\http\Middleware;

/**
 * HTTP进程服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Http extends Process
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        // 监听协议断开
        'listen'    => 'http://0.0.0.0:8080',
        // 通信协议
        'transport' => 'tcp',
        // 额外参数
        'context'   => [],
        // 进程数
        'count'     =>  2,
        // 进程用户
        'user'      => '',
        // 进程用户组
        'group'     => '',
        // 是否开启端口复用
        'reusePort' => false,
    ];

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker)
    {
        // 运行模式
        $debug = Config::instance()->get('app.debug', true);

        $httpConfig = Config::instance()->get('http');
        $appConfig = $httpConfig['app'];
        $errorHandler = Container::instance()->get($appConfig['exception']);
        // 初始化HTTP服务器
        $app = new App;
        $app->init($worker, $errorHandler, $debug);

        // 应用扩展支持
        $app->suppertCallback($appConfig['reusecall'], $appConfig['request'], $appConfig['max_cache']);

        // 静态文件支持
        $staticConfig = $httpConfig['static'];
        $app->supportStaticFile($staticConfig['enable'], $staticConfig['path'], $staticConfig['ext_type']);

        // session扩展支持
        $sessionConfig = $httpConfig['session'];
        $app->supportSession($sessionConfig['handler'], $sessionConfig['setting'], $sessionConfig);

        // 全局中间件
        $middlewareConfig = $httpConfig['middleware'];
        Middleware::instance()->load($middlewareConfig);

        // 注册路由
        $this->registerRoute($app->route());

        // 绑定响应请求
        $worker->onMessage = [$app, 'onMessage'];
    }

    /**
     * 注册路由
     *
     * @param Route $route
     * @return void
     */
    protected function registerRoute(\mon\http\Route $route)
    {
        // 注册路由
        require APP_PATH . '/http/router.php';
    }
}
