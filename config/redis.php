<?php
/*
|--------------------------------------------------------------------------
| Redis缓存配置文件
|--------------------------------------------------------------------------
| 定义Redis链接信息
|
*/

return [
    // 链接host
    'host'          => env('REDIS_HOST', '127.0.0.1'),
    // 链接端口
    'port'          => env('REDIS_PORT', 6379),
    // 链接密码
    'auth'          => env('REDIS_AUTH', ''),
    // 自定义键前缀
    'prefix'        => env('REDIS_PREFIX', ''),
    // redis数据库
    'database'      => env('REDIS_DB', 0),
    // 读取超时时间
    'timeout'       => env('REDIS_TIMEOUT', 2),
    // 连接保活，0则不保活
    'ping'          => env('REDIS_PING', 0),
    // 保持链接
    'persistent'    => env('REDIS_PERSISTENT', false),
];
