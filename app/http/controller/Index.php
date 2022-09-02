<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\http\Request;
use app\model\UserModel;
use app\service\LogService;
use mon\env\Config;

/**
 * 首页控制器
 */
class Index
{
    /**
     * 首页控制器
     *
     * @param Request $request
     * @return string
     */
    public function index(Request $request)
    {
        LogService::instance()->send('info', 'test123', 'http', true);
        LogService::instance()->debug('test debug', 'http', true);
        return 'Hello Controller!';
    }

    /**
     * 用户列表
     *
     * @param Request $request
     * @return array
     */
    public function list(Request $request): array
    {
        // $userList = UserModel::instance()->getUser();
        return Config::instance()->get('http.app');
    }
}
