<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\http\Request;
use app\http\controller\Index;

/** @var \mon\http\Route $route */
$route->get('/', function (Request $request) {
    return 'Hello Gaia!';
});

$route->get('/index', [Index::class, 'index']);

$route->get('/list', [Index::class, 'list']);
