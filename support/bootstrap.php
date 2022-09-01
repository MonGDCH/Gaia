<?php

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

/*
|--------------------------------------------------------------------------
| 错误信息处理
|--------------------------------------------------------------------------
| 这里定义PHP错误处理，开启所有错误信息
|
*/
ini_set('display_errors', 'on');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| 定义应用根路径
|--------------------------------------------------------------------------
| 这里定义根路径, 用于路径查找
|
*/
define('ROOT_PATH', dirname(__DIR__));


/*
|--------------------------------------------------------------------------
| 定义配置文件路径
|--------------------------------------------------------------------------
| 这里定义配置文件路径, 用于路径查找获取配置信息
|
*/
define('CONFIG_PATH', ROOT_PATH . '/config');


/*
|--------------------------------------------------------------------------
| 运行时目录路径
|--------------------------------------------------------------------------
| 这里定义运行时路径, 用于存储系统运行时相关资源内容，目录需拥有读写权限
|
*/
define('RUNTIME_PATH', ROOT_PATH . '/runtime');


/*
|--------------------------------------------------------------------------
| 进程驱动目录路径
|--------------------------------------------------------------------------
| 这里定义进程驱动类库存在目录，目录最好拥有读写权限
|
*/
define('PROCESS_PATH', ROOT_PATH . '/process');


/*
|--------------------------------------------------------------------------
| 定义应用APP路径
|--------------------------------------------------------------------------
| 这里定义用户主要编写业务代码存放路径, 用于路径查找
|
*/
define('APP_PATH', ROOT_PATH . '/app');


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



// 默认的最大可接受数据包大小
TcpConnection::$defaultMaxPackageSize = 10 * 1024 * 1024;
// 存储主进程PID的文件
Worker::$pidFile = RUNTIME_PATH . '/gaia.pid';
// 存储标准输出的文件，默认 /dev/null。daemonize运行模式下echo的内容才会记录到文件中
Worker::$stdoutFile = RUNTIME_PATH . '/stdout.log';
// workerman日志记录文件
Worker::$logFile = RUNTIME_PATH . '/workerman.log';
// 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
Worker::$statusFile = RUNTIME_PATH . '/gaia.status';
// workerman事件循环使用对象，默认 \Workerman\Events\Select。一般不需要修改，空则可以
Worker::$eventLoopClass = '';
// 发送停止命令后，多少秒内程序没有停止，则强制停止
Worker::$stopTimeout = 2;
// 重置opcache
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
