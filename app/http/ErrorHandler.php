<?php

declare(strict_types=1);

namespace app\http;

use Throwable;
use mon\http\Request;
use app\service\LogService;

/**
 * 自定义HTTP异常处理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ErrorHandler extends \mon\http\support\ErrorHandler
{
    /**
     * 上报异常信息
     *
     * @param Throwable $e  错误实例
     * @param Request $request  请求实例
     * @return void
     */
    public function report(Throwable $e, Request $request)
    {
        $log = 'method：' . $request->method() . ' URL：' . $request->path() . ' file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' message: ' . $e->getMessage();
        LogService::instance()->error($log, 'http');
    }
}
