<?php
/*
|--------------------------------------------------------------------------
| 应用配置文件
|--------------------------------------------------------------------------
| 定义应用配置信息
|
*/

return [
    // 是否调试模式
    'debug'             => true,
    // 时区
    'timezone'          => 'PRC',
    // 存储主进程PID的文件
    'pid_file'          => RUNTIME_PATH . '/gaia.pid',
    // 存储主进程状态文件的文件
    'status_file'       => RUNTIME_PATH . '/gaia.status',
    // 存储标准输出的文件
    'stdout_file'       => RUNTIME_PATH . '/stdout.log',
    // 存储日志文件
    'log_file'          => RUNTIME_PATH . '/workerman.log',
    // 默认的最大可接受数据包大小
    'max_package_size'  => 10 * 1024 * 1024,
    // workerman事件循环使用对象，默认 \Workerman\Events\Select
    'event_loop'        => '',
    // 发送停止命令后，多少秒内程序没有停止，则强制停止
    'stop_timeout'      => 2
];
