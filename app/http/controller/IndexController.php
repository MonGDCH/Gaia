<?php

declare(strict_types=1);

namespace app\http\controller;

use Exception;
use mon\http\Request;
use mon\log\LoggerFactory;
use mon\http\support\Controller;

/**
 * 首页控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class IndexController extends Controller
{
    /**
     * 首页控制器
     *
     * @param Request $request
     * @return string
     */
    public function index(Request $request)
    {
        LoggerFactory::instance()->channel('http')->debug('test', ['trace' => true]);

        return 'Hello Controller!';
    }
}
