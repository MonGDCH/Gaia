<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\http\Route;
use app\http\middleware\WxAuth;

/** @var \mon\http\Route $route */
$route->group(['path' => '/consumption', 'namespace' => 'app\http\controller\\'], function (Route $router) {

    // 登录
    $router->group('/user', function (Route $router) {
        // 登录
        $router->post('/login/{code}', 'UserController@login');
        // 注册
        $router->post('/register/{code}', 'UserController@register');
    });

    // 用户相关
    $router->group(['path' => '/user', 'middleware' => WxAuth::class], function (Route $router) {
        // 刷新token
        $router->post(['path' => '/refresh'], 'UserController@refreshToken');
        // 用户信息
        $router->get(['path' => '/info'], 'UserController@info');
        // 订阅信息
        $router->get(['path' => '/subscribe'], 'UserController@getSubscribe');
        // 记录订阅信息
        $router->post(['path' => '/subscribe/save'], 'UserController@saveSubscribe');
    });

    // 账本
    $router->group(['path' => '/book', 'middleware' => WxAuth::class], function (Route $router) {
        // 查询
        $router->get('/query/{admin:\d+}', 'BookController@query');
        // 添加
        $router->post('/add', 'BookController@add');
        // 修改
        $router->post('/modify/{book_id:\d+}', 'BookController@modify');
        // 删除
        $router->post('/remove', 'BookController@remove');
        // 获取账本用户
        $router->get('/get/user/{book_id:\d+}', 'BookController@getBookUser');
        // 移除退出账本
        $router->post('/quit/{book_id:\d+}/{quit_id:\d+}', 'BookController@quit');
        // 获取邀请码
        $router->get('/apply/{book_id:\d+}', 'BookController@applyCode');
        // 获取邀请信息
        $router->get('/apply/info/{code}', 'BookController@getApplyInfo');
        // 加入账本
        $router->post('/attend/{code}', 'BookController@attend');
    });

    // 分类
    $router->group(['path' => '/cate', 'middleware' => WxAuth::class], function (Route $router) {
        // 获取分类
        $router->get('/get/{type:\d+}/{custom:\d+}', 'CateController@getCate');
        // 添加
        $router->post('/add', 'CateController@add');
        // 修改
        $router->post('/modify/{cate_id:\d+}', 'CateController@modify');
        // 删除
        $router->post('/remove', 'CateController@remove');
    });

    // 记录
    $router->group(['path' => '/record', 'middleware' => WxAuth::class], function (Route $router) {
        // 图片附件上传
        $router->post('/img/upload', 'RecordController@upload');
        // 获取账本列表
        $router->get('/list/{pageSize:\d+}/{page:\d+}/{book_id:\d+}/{date}', 'RecordController@list');
        // 添加
        $router->post('/add', 'RecordController@add');
        // 编辑
        $router->post('/modify/{record_id:\d+}', 'RecordController@modify');
        // 删除
        $router->post('/remove', 'RecordController@remove');
        // 读取记录详情
        $router->get('/get/{record_id:\d+}', 'RecordController@get');
        // 获取统计分析数据
        $router->get('/statis/{book_id:\d+}/{type:\d+}/{date}', 'RecordController@statis');
    });
});
