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
    // 监控服务
    'monitor'   => [
        // 监控的文件目录
        'paths' => [APP_PATH, CONFIG_PATH],
        // 监控指定后缀名文件
        'exts'  => ['php', 'html'],
    ],
    // phar包生成
    'phar'      => [
        // phar包保存路径
        'dirname'   => ROOT_PATH . '/build',
        // phar包名称
        'filename'  => 'gaia.phar',
        // phar包加密算法
        'algorithm' => Phar::SHA256,
        // 忽略的文件
        'exclude_files' => ['LICENSE', 'README.md', 'composer.json', 'composer.lock']
    ]
];
