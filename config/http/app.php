<?php

return [
    // 异常错误处理器
    'exception' => \mon\http\support\ErrorHandler::class,
    // 请求处理器
    'request'   => \mon\http\Request::class,
    // 是否每次业务重新创建控制器
    'reusecall' => true,
    // 最大缓存回调路由数
    'max_cache' => 512,
];
