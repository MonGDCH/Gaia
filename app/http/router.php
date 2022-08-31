<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\http\Route;
use mon\http\Request;
use app\http\controller\Index;
use Channel\Client;

Route::instance()->get('/', function (Request $request) {
    // Client::enqueue('log', 'test => ' . random_int(1, 100));
    echo 16;
    return 'Hello Gaia';
});

Route::instance()->get('/index', [Index::class, 'index']);

Route::instance()->get('/list', [Index::class, 'list']);
