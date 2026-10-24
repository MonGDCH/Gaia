<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 定义 {{DESC}} 路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\http\Route;
use app\http\controller\{{MODULE}}\{{NAME}}Controller;

Route::instance()->group('/{{PREFIX}}', function (Route $route) {
    // 列表
    $route->get('', [{{NAME}}Controller::class, 'index'])->name('{{PREFIX}}.index');
    // 详情
    $route->get('/detail/{id}', [{{NAME}}Controller::class, 'detail'])->name('{{PREFIX}}.detail');
    // 新增
    $route->post('/add', [{{NAME}}Controller::class, 'add'])->name('{{PREFIX}}.add');
});
