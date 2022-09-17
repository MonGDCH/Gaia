<?php
/*
|--------------------------------------------------------------------------
| 应用初始化执行业务
|--------------------------------------------------------------------------
| 定义全局应用初始化执行业务 
|
*/

use app\bootstrap\LoggerBootstrap;
use app\bootstrap\UCenterBootstrap;

return [
    LoggerBootstrap::class,
    UCenterBootstrap::class
];
