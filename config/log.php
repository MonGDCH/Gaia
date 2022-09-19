<?php
/*
|--------------------------------------------------------------------------
| 日志配置文件
|--------------------------------------------------------------------------
| 定义日志配置信息
|
*/

use mon\log\format\LineFormat;
use mon\log\record\FileRecord;

return [
    // 通道
    'default' => [
        // 解析器
        'format'    => [
            // 类名
            'handler'   => LineFormat::class,
            // 配置信息
            'config'    => []
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                // 日志文件大小
                'maxSize'   => 20480000,
                // 日志目录
                'logPath'   => RUNTIME_PATH . '/log',
                // 日志滚动卷数   
                'rollNum'   => 3,
                // 日志名称，空则使用当前日期作为名称       
                'logName'   => '',
            ]
        ]
    ],
];
