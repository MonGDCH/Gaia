<?php
/*
|--------------------------------------------------------------------------
| 日志配置文件
|--------------------------------------------------------------------------
| 定义日志配置信息
|
*/

return [
    'default'  => [
        'logPath'   => RUNTIME_PATH . '/log',
        'rollNum'   => 3,
        'splitLine' => '',
        'save'      => true
    ],
    'http'  => [
        'logPath'   => RUNTIME_PATH . '/log/http',
        'rollNum'   => 3,
        'splitLine' => '',
        'save'      => true
    ],
    'error'  => [
        'logPath'   => RUNTIME_PATH . '/log/error',
        'rollNum'   => 3,
        'splitLine' => '',
        'save'      => true
    ]
];
