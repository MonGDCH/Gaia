<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\orm\Db;
use mon\http\Request;
use mon\http\Response;
use mon\ucenter\UCenter;
use app\service\JwtService;
use app\service\WechatService;
use mon\http\support\Controller;
use app\model\consumption\ViwUserBookModel;
use app\model\consumption\ConsumptionRecordModel;
use app\model\consumption\ConsumptionSubscribeModel;

/**
 * 用户相关控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserController extends Controller
{
    /**
     * 微信平台编号
     *
     * @var integer
     */
    protected $wechat = 1;

    /**
     * 登录
     *
     * @param Request $request
     * @param string $code
     * @return Response
     */
    public function login(Request $request, string $code): Response
    {
        // 获取openid
        $openData = WechatService::instance()->getOpenid($code);
        if (!$openData) {
            return $this->error(WechatService::instance()->getError());
        }

        // 验证用户授权
        $openUserInfo = UCenter::instance()->openAccount()->getUser($openData['openid'], $this->wechat);
        if (!$openUserInfo) {
            return $this->error('用户未授权');
        }

        // 获取用户信息
        $userInfo = UCenter::instance()->user()->getInfo(['id' => $openUserInfo['uid']], ['nickname', 'avatar']);
        if (!$userInfo) {
            return $this->error('获取用户信息失败!');
        }
        // 已登录注册，直接返回token
        $token = JwtService::instance()->create($openUserInfo['uid']);
        if (!$token) {
            return $this->error('生成登录Token失败!');
        }

        return $this->success('ok', [
            'uid'       => $openUserInfo['uid'],
            'nickname'  => $userInfo['nickname'],
            'avatar'    => $userInfo['avatar'],
            'token'     => [
                'token_key'     => $token,
                'token_expires' => JwtService::instance()->getConfig('exp'),
                'token_create'  => time(),
            ]
        ]);
    }

    /**
     * 注册
     *
     * @param Request $request
     * @param string $code
     * @return Response
     */
    public function register(Request $request, string $code): Response
    {
        // 验证参数
        $option = $request->post();
        if (!isset($option['nickname']) || empty($option['nickname']) || !isset($option['avatar']) || empty($option['avatar'])) {
            return $this->error('params faild');
        }
        // 获取openid
        $openData = WechatService::instance()->getOpenid($code);
        if (!$openData) {
            return $this->error(WechatService::instance()->getError());
        }

        // 判断是否已注册
        $isRegister = UCenter::instance()->openAccount()->getUser($openData['openid'], $this->wechat);
        if ($isRegister) {
            // 已登录注册，直接返回token
            $token = JwtService::instance()->create($isRegister['uid']);
            if (!$token) {
                return $this->error('生成登录Token失败!');
            }

            // 更新用户信息
            $userModel = UCenter::instance()->user();
            $saveUser = $userModel->edit([
                'nickname'  => $option['nickname'],
                'avatar'    => $option['avatar'],
                'status'    => 1
            ], $isRegister['uid']);
            if (!$saveUser) {
                return $this->error($userModel->getError());
            }

            return $this->success('ok', [
                'uid'       => $isRegister['uid'],
                'nickname'  => $option['nickname'],
                'avatar'    => $option['avatar'],
                'token'     => [
                    'token_key'     => $token,
                    'token_expires' => JwtService::instance()->getConfig('exp'),
                    'token_create'  => time(),
                ]
            ]);
        }
    }

    /**
     * 刷新用户tokens
     *
     * @param Request $request
     * @return Response
     */
    public function refreshToken(Request $request): Response
    {
        $token = JwtService::instance()->create($request->uid);
        if (!$token) {
            return $this->error('生成登录Token失败!');
        }

        return $this->success('ok', [
            'token_key'     => $token,
            'token_expires' => JwtService::instance()->getConfig('exp'),
            'token_create'  => time(),
        ]);
    }

    /**
     * 获取登录用户信息
     *
     * @param Request $request
     * @return Response
     */
    public function info(Request $request): Response
    {
        $uid = $request->uid ?? 0;
        $userTable = UCenter::instance()->user()->getTable();
        $recordTable = ConsumptionRecordModel::instance()->getTable();
        $sql = "SELECT
                    `user`.id,
                    `user`.nickname,
                    `user`.avatar,
                    COUNT( `record`.id ) AS total_record,
                    COUNT( DISTINCT `record`.oper_time ) AS total_date 
                FROM
                    {$userTable} AS `user`
                    LEFT JOIN {$recordTable} AS `record` ON `user`.id = `record`.uid 
                WHERE
                    `user`.id = {$uid}";

        $queryData = Db::connect()->query($sql);
        $data = $queryData ? $queryData[0] : [];

        $total_book = ViwUserBookModel::instance()->where('uid', $uid)->count('book_id');
        $data['total_book'] = $total_book;

        return $this->success('ok', $data);
    }

    /**
     * 获取订阅信息
     *
     * @param Request $request
     * @return Response
     */
    public function getSubscribe(Request $request): Response
    {
        $info = ConsumptionSubscribeModel::instance()->getInfo($request->uid);
        return $this->success('ok', $info);
    }

    /**
     * 保存订阅信息
     *
     * @param Request $request
     * @return Response
     */
    public function saveSubscribe(Request $request): Response
    {
        $data = $request->post();
        $save = ConsumptionSubscribeModel::instance()->subscribe($data, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionSubscribeModel::instance()->getError());
        }

        return $this->success('ok');
    }
}
