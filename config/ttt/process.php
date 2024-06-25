<?php

/*
|--------------------------------------------------------------------------
| 自定义进程 Ttt 服务启动配置文件
|--------------------------------------------------------------------------
| 定义自定义进程 Ttt 服务启动配置
|
*/

return [
    // 启用
    'enable'    => true,
    // 进程配置
    'config'    => [
        // 监听协议端口
        'listen'        => '',
        // 额外参数
        'context'       => [],
        // 进程数
        'count'         => 1,
        // 通信协议，一般不需要修改
        'transport'     => 'tcp',
        // 进程用户
        'user'          => '',
        // 进程用户组
        'group'         => '',
        // 是否开启端口复用
        'reusePort'     => false,
        // 是否允许进程重载
        'reloadable'    => true,
    ]
];