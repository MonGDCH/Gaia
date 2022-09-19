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
$route->get(['path' => '/'], [\app\http\controller\IndexController::class, 'index']);

// API定义
$route->group(['path' => '/consumption', 'namespace' => 'app\http\controller\\'], function (Route $route) {

    // 登录
    $route->group('/user', function (Route $route) {
        // 登录
        $route->post('/login/{code}', 'UserController@login');
        // 注册
        $route->post('/register/{code}', 'UserController@register');
    });

    // 用户相关
    $route->group(['path' => '/user', 'middleware' => WxAuth::class], function (Route $route) {
        // 刷新token
        $route->post(['path' => '/refresh'], 'UserController@refreshToken');
        // 用户信息
        $route->get(['path' => '/info'], 'UserController@info');
        // 订阅信息
        $route->get(['path' => '/subscribe'], 'UserController@getSubscribe');
        // 记录订阅信息
        $route->post(['path' => '/subscribe/save'], 'UserController@saveSubscribe');
    });

    // 账本
    $route->group(['path' => '/book', 'middleware' => WxAuth::class], function (Route $route) {
        // 查询
        $route->get('/query/{admin:\d+}', 'BookController@query');
        // 添加
        $route->post('/add', 'BookController@add');
        // 修改
        $route->post('/modify/{book_id:\d+}', 'BookController@modify');
        // 删除
        $route->post('/remove', 'BookController@remove');
        // 获取账本用户
        $route->get('/get/user/{book_id:\d+}', 'BookController@getBookUser');
        // 移除退出账本
        $route->post('/quit/{book_id:\d+}/{quit_id:\d+}', 'BookController@quit');
        // 获取邀请码
        $route->get('/apply/{book_id:\d+}', 'BookController@applyCode');
        // 获取邀请信息
        $route->get('/apply/info/{code}', 'BookController@getApplyInfo');
        // 加入账本
        $route->post('/attend/{code}', 'BookController@attend');
    });

    // 分类
    $route->group(['path' => '/cate', 'middleware' => WxAuth::class], function (Route $route) {
        // 获取分类
        $route->get('/get/{type:\d+}/{custom:\d+}', 'CateController@getCate');
        // 添加
        $route->post('/add', 'CateController@add');
        // 修改
        $route->post('/modify/{cate_id:\d+}', 'CateController@modify');
        // 删除
        $route->post('/remove', 'CateController@remove');
    });

    // 记录
    $route->group(['path' => '/record', 'middleware' => WxAuth::class], function (Route $route) {
        // 图片附件上传
        $route->post('/img/upload', 'RecordController@upload');
        // 获取账本列表
        $route->get('/list/{pageSize:\d+}/{page:\d+}/{book_id:\d+}/{date}', 'RecordController@list');
        // 添加
        $route->post('/add', 'RecordController@add');
        // 编辑
        $route->post('/modify/{record_id:\d+}', 'RecordController@modify');
        // 删除
        $route->post('/remove', 'RecordController@remove');
        // 读取记录详情
        $route->get('/get/{record_id:\d+}', 'RecordController@get');
        // 获取统计分析数据
        $route->get('/statis/{book_id:\d+}/{type:\d+}/{date}', 'RecordController@statis');
    });
});
