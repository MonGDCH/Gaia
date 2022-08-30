<?php

/*
|--------------------------------------------------------------------------
| 监控服务配置文件
|--------------------------------------------------------------------------
| 定义监控服务配置信息
|
*/

return [
    // 监控的文件目录
    'paths' => [APP_PATH, CONFIG_PATH, PROCESS_PATH],
    // 监控指定后缀名文件
    'exts'  => ['php', 'html'],
];
