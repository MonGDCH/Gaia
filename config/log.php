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
            'config'    => [
                // 日志是否包含级别
                'level'         => true,
                // 日志是否包含时间
                'date'          => true,
                // 时间格式，启用日志时间时有效
                'date_format'   => 'Y-m-d H:i:s',
                // 是否启用日志追踪
                'trace'         => false,
                // 追踪层级，启用日志追踪时有效
                'layer'         => 3
            ]
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                // 是否自动写入文件
                'save'      => true,
                // 写入文件后，清除缓存日志
                'clear'     => true,
                // 日志名称，空则使用当前日期作为名称       
                'logName'   => '',
                // 日志文件大小
                'maxSize'   => 20480000,
                // 日志目录
                'logPath'   => RUNTIME_PATH . '/log',
                // 日志滚动卷数   
                'rollNum'   => 3
            ]
        ]
    ]
];
