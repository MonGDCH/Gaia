<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\http\Request;
use app\model\UserModel;
use Channel\Client;
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
        Client::enqueue('log', 'test => ' . random_int(1, 100));
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
