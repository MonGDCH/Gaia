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
    'debug'     => env('APP_DEBUG', true),
    // 时区
    'timezone'  => 'PRC',
    // 监控服务
    'monitor'   => [
        // 监控的文件目录
        'paths' => [APP_PATH, CONFIG_PATH, PLUGIN_PATH, SUPPORT_PATH],
        // 监控指定后缀名文件
        'exts'  => ['php', 'html']
    ],
    // 框架钩子
    'hooks'   => [
        // 应用初始化
        'app_init'      => [],
        // 应用启动
        'app_start'     => [],
        // 应用加载进程
        'app_run'       => [],
        // 基础初始化
        'process_init'  => [],
        // 进程启动
        'process_start' => [],
        // 进程错误
        'process_error' => []
    ],
    // worker进程配置
    'worker'    => [
        // 默认的最大可接受数据包大小
        'max_package_size'  => 10 * 1024 * 1024,
        // 存储主进程PID的文件
        'pid_file'          => 'gaia.pid',
        // 存储关闭服务标准输出的文件
        'stdout_file'       => 'stdout.log',
        // workerman日志记录文件
        'log_file'          => 'workerman.log',
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        'status_file'       => 'gaia.status',
        // workerman事件循环使用对象，默认 \Workerman\Events\Select。一般不需要修改，空则可以
        'event_loop_class'  => '',
        // 发送停止命令后，多少秒内程序没有停止，则强制停止
        'stop_timeout'      => 2
    ]
];
