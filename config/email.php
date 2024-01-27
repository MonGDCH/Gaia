<?php

/*
|--------------------------------------------------------------------------
| Email邮件服务配置文件
|--------------------------------------------------------------------------
| 定义Email服务信息
|
*/

return [
    // 服务地址
    'host'      => env('EMAIL_HOST', 'smtp.qq.com'),
    // 服务端口
    'port'      => env('EMAIL_PROT', 465),
    // 用户名
    'user'      => env('EMAIL_USER', ''),
    // 密码
    'password'  => env('EMAIL_PASSWORD', ''),
    // 是否使用SSL
    'ssl'       => env('EMAIL_SSL', true),
    // 发件人
    'from'      => env('EMAIL_FORM', ''),
    // 发件人名称
    'name'      => env('EMAIL_NAME', ''),
];
