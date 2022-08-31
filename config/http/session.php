<?php

return [
    // session驱动
    'handler'               => \Workerman\Protocols\Http\Session\FileSessionHandler::class,
    // 驱动初始化传入参数
    'setting'               => [
        // 文件session保存路径
        'save_path' => RUNTIME_PATH . '/sess/',
    ],
    // session名称，默认：PHPSID
    'session_name'          => 'PHPSID',
    // 自动更新时间，默认：false
    'auto_update_timestamp' => false,
    // cookie有效期，默认：1440
    'cookie_lifetime'       => 1440,
    // cookie路径，默认：/
    'cookie_path'           => '/',
    // 同站点cookie，默认：''
    'same_site'             => '',
    // cookie的domain，默认：''
    'domain'                => '',
    // 是否仅适用https的cookie，默认：false
    'secure'                => false,
    // session有效期，默认：1440
    'lifetime'              => 1440,
    // 是否开启http_only，默认：true
    'http_only'             => true,
    // gc的概率，默认：[1, 1000]
    'gc_probability'        => [1, 1000],
];
