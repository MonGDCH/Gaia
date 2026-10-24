<?php

declare(strict_types=1);

namespace app\http\middleware\{{MODULE}};

use Closure;
use mon\log\Logger;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;

/**
 * {{NAME}} 中间件
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Middleware implements MiddlewareInterface
{
    /**
     * 中间件处理
     *
     * @param RequestInterface $request 请求实例
     * @param Closure $next 下一个中间件回调
     * @return Response
     */
    public function process(RequestInterface $request, Closure $next): Response
    {
        try {
            // TODO: 前置逻辑（鉴权、限流、上下文注入等）
        } catch (\Throwable $e) {
            Logger::error('{{NAME}} 中间件前置处理失败', ['error' => $e->getMessage()]);

            return new Response(500, [], 'Server Error');
        }

        $response = $next($request);

        // TODO: 后置逻辑

        return $response;
    }
}
