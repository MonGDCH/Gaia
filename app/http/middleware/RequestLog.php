<?php

declare(strict_types=1);

namespace app\http\middleware;

use Closure;
use mon\http\Request;
use mon\http\Response;
use app\service\LogService;
use mon\http\interfaces\Middleware;

/**
 * 请求日志中间件
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RequestLog implements Middleware
{
    /**
     * 中间件实现接口
     *
     * @param Request $request  请求实例
     * @param Closure $callback 执行下一个中间件回调方法
     * @return Response
     */
    public function process(Request $request, Closure $callback): Response
    {
        // 请求路径
        $path = $request->path();
        // 请求方式
        $method = $request->method();
        // 请求IP
        $ip = $request->getRemoteIp();
        // 本地IP
        $local = $request->getLocalIp();

        $log = "{$local} {$ip} {$method} {$path}";
        LogService::instance()->info($log, 'http');

        return $callback($request);
    }
}
