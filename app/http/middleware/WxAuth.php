<?php

declare(strict_types=1);

namespace app\http\middleware;

use Closure;
use mon\http\Jump;
use mon\http\Request;
use mon\http\Response;
use app\service\JwtService;
use mon\http\interfaces\Middleware;

/**
 * 微信JWT登录权限
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class WxAuth implements Middleware
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
        // 验证微信发起请求
        // if (mb_strpos($request->header('user-agent'), 'MicroMessenger') === false) {
        //     // 非微信浏览器
        //     return Jump::instance()->abort(404);
        // }
        // 验证token
        $token = $request->header('mon-wxapp-token');
        if (!$token) {
            return Jump::instance()->abort(403);
        }
        // 判断token是否过期无效
        $info = JwtService::instance()->check($token);
        if (!$info) {
            return Jump::instance()->abort(401);
        }

        var_dump($info);

        // 记录用户ID
        $request->uid = $info['sub'];

        return $callback($request);
    }
}
