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
    'debug'     => true,
    // 时区
    'timezone'  => 'PRC',
    // worker进程配置
    'worker'    => [
        // 默认的最大可接受数据包大小
        'max_package_size'  => 10 * 1024 * 1024,
        // 存储主进程PID的文件
        'pid_file'          => RUNTIME_PATH . '/gaia.pid',
        // workerman日志记录文件
        'log_file'          => RUNTIME_PATH . '/workerman.log',
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        'status_file'       => RUNTIME_PATH . '/gaia.status',
        // workerman事件循环使用对象，默认 \Workerman\Events\Select。一般不需要修改，空则可以
        'event_loop_class'  => '',
        // 发送停止命令后，多少秒内程序没有停止，则强制停止
        'stop_timeout'      => 2
    ],
    // 监控服务
    'monitor'   => [
        // 监控的文件目录
        'paths' => [APP_PATH, CONFIG_PATH],
        // 监控指定后缀名文件
        'exts'  => ['php', 'html'],
    ]
];
