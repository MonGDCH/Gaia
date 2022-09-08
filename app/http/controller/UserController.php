<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\http\Request;

class UserController
{
    /**
     * 登录
     *
     * @param string $code
     * @return void
     */
    public function login(Request $request, string $code)
    {
        $openData = WechatService::instance()->getOpenid($code);
        if (!$openData) {
            return $this->error(WechatService::instance()->getError());
        }

        $openUserInfo = UserOpenAccountModel::instance()->getUser($openData['openid'], $this->wechat);
        if (!$openUserInfo) {
            return $this->error('用户未授权');
        }

        $userInfo = UserModel::instance()->getInfo(['id' => $openUserInfo['uid']], ['nickname', 'avatar']);
        if (!$userInfo) {
            return $this->error('获取用户信息失败!');
        }
        // 已登录注册，直接返回token
        $token = Jwt::instance()->create($openUserInfo['uid']);
        if (!$token) {
            return $this->error('生成登录Token失败!');
        }
        // 获取账本信息

        return $this->success('ok', [
            'uid'       => $openUserInfo['uid'],
            'nickname'  => $userInfo['nickname'],
            'avatar'    => $userInfo['avatar'],
            'token'     => [
                'token_key' => $token,
                'token_expires' => Config::instance()->get('app.jwt.exp'),
                'token_create' => time(),
            ]
        ]);
    }
}
