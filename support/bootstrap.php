<?php

use mon\env\Config;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

/*
|--------------------------------------------------------------------------
| 加载composer
|--------------------------------------------------------------------------
| 加载composer, 处理类文件自动加载
|
*/
require_once __DIR__ . '/../vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| 接管错误处理
|--------------------------------------------------------------------------
| 这里定义PHP错误及程序结束处理方法
|
*/
set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
    if (error_reporting() & $level) {
        // 所有错误抛出异常
        throw new ErrorException($message, 0, $level, $file, $line);
    }
});
register_shutdown_function(function ($start_time) {
    // 进程平滑开关
    if (Worker::getAllWorkers()) {
        if (time() - $start_time <= 1) {
            sleep(1);
        }
    }
}, time());


/*
|--------------------------------------------------------------------------
| 注册配置信息
|--------------------------------------------------------------------------
| 这里注册加载全局配置信息
|
*/
Config::instance()->loadDir(CONFIG_PATH);


/*
|--------------------------------------------------------------------------
| 定义应用时区
|--------------------------------------------------------------------------
| 这里设置时区
|
*/
date_default_timezone_set(Config::instance()->get('app.timezone', 'PRC'));


/*
|--------------------------------------------------------------------------
| 加载自动加载的文件
|--------------------------------------------------------------------------
| 这里加载配置自动加载的文件
|
*/
foreach (Config::instance()->get('autoload', []) as $file) {
    include_once $file;
}


/*
|--------------------------------------------------------------------------
| 定义全局进程配置信息
|--------------------------------------------------------------------------
| 这里定义全局的进程配置
|
*/
TcpConnection::$defaultMaxPackageSize = Config::instance()->get('app.max_package_size', (10 * 1024 * 1024));
Worker::$pidFile = Config::instance()->get('app.pid_file', '');
Worker::$stdoutFile = Config::instance()->get('app.stdout_file', '/dev/null');
Worker::$logFile = Config::instance()->get('app.log_file', '');
Worker::$statusFile = Config::instance()->get('app.status_file', '');
Worker::$eventLoopClass = Config::instance()->get('app.event_loop', '');
Worker::$stopTimeout = Config::instance()->get('app.stop_timeout', 2);
Worker::$onMasterReload = function () {
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        if ($status && isset($status['scripts'])) {
            foreach (array_keys($status['scripts']) as $file) {
                opcache_invalidate($file, true);
            }
        }
    }
};

