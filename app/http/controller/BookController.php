<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\http\Request;
use mon\http\Response;
use mon\http\support\Controller;
use app\model\consumption\ViwUserBookModel;
use app\model\consumption\ConsumptionBookModel;
use app\model\consumption\ConsumptionBookAccessModel;

/**
 * 账户控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class BookController extends Controller
{
    /**
     * 邀请码加密盐
     *
     * @var string
     */
    protected $apply_salt = 'monApplySalt';

    /**
     * 查询账本列表
     *
     * @param Request $request  请求实例
     * @param integer $admin    1则表示为管理员，其他则所有
     * @return Response
     */
    public function query(Request $request, $admin): Response
    {
        $field = ['book_id AS id', 'title', 'admin', 'uid', 'nickname', 'adminUser', 'adminUid'];
        $qeruy = ViwUserBookModel::instance()->where('status', 1)->where('uid', $request->uid)->order('admin', 'DESC')->field($field);
        if ($admin) {
            $data = $qeruy->where('admin', 1)->select();
        } else {
            $data = $qeruy->select();
        }
        return $this->success('ok', $data);
    }

    /**
     * 新增账本
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $title = $request->post('title');
        if (!$title || !is_string($title) || mb_strlen($title) > 6) {
            return $this->error('请输入合法账本名称');
        }

        $save = ConsumptionBookModel::instance()->add($title, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionBookModel::instance()->getError());
        }

        return $this->success('操作成功');
    }

    /**
     * 编辑账本
     *
     * @param Request $request  请求实例
     * @param integer $book_id  账本ID
     * @return Response
     */
    public function modify(Request $request, int $book_id): Response
    {
        $option = $request->post();
        $save = ConsumptionBookModel::instance()->modify($book_id, $option, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionBookModel::instance()->getError());
        }

        return $this->success('操作成功');
    }

    /**
     * 删除账本
     *
     * @param Request $request
     * @return Response
     */
    public function remove(Request $request): Response
    {
        $idx = $request->post('book_id');
        if (!check('int', $idx)) {
            return $this->error('params faild');
        }

        // 校验不能是最后一个账本
        if (ConsumptionBookModel::instance()->where('status', 1)->where('uid', $request->uid)->count() < 1) {
            return $this->error('必须至少拥有一本账本');
        }

        // 删除
        $save = ConsumptionBookModel::instance()->remove($idx, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionBookModel::instance()->getError());
        }

        return $this->success('操作成功');
    }

    /**
     * 获取账本用户
     *
     * @param Request $request  请求实例
     * @param integer $book_id  账本ID
     * @return Response
     */
    public function getBookUser(Request $request, int $book_id): Response
    {
        $data = ViwUserBookModel::instance()->where('book_id', $book_id)->field(['uid AS id', 'nickname', 'avatar'])->select();
        return $this->success('ok', $data);
    }

    /**
     * 移出账本
     *
     * @param Request $request  请求实例
     * @param integer $book_id  账本ID
     * @param integer $quit_id  移出用户ID
     * @return Response
     */
    public function quit(Request $request, int $book_id, int $quit_id): Response
    {
        $save = ConsumptionBookAccessModel::instance()->quit($book_id, $quit_id, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionBookAccessModel::instance()->getError());
        }

        return $this->success('操作成功');
    }

    /**
     * 账本邀请码
     *
     * @param Request $request  请求实例
     * @param integer $book_id  账本ID
     * @return Response
     */
    public function applyCode(Request $request, int $book_id): Response
    {
        $isAdmin = ConsumptionBookModel::instance()->where('id', $book_id)->where('uid', $request->uid)->where('status', 1)->find();
        if (!$isAdmin) {
            return $this->error('没有权限');
        }

        $code = time() . '_' . $book_id . '_' . $request->uid;
        $encryption = encryption($code, $this->apply_salt);
        return $this->success('ok', ['code' => $encryption]);
    }

    /**
     * 获取邀请信息
     *
     * @param Request $request  请求实例
     * @param string $code      邀请码
     * @return Response
     */
    public function getApplyInfo(Request $request, string $code): Response
    {
        $decryption = decryption($code, $this->apply_salt);
        if (!$decryption) {
            return $this->error('params faild');
        }
        list($time, $book_id, $book_uid) = explode('_', $decryption, 3);
        if (!check('int', $time) || ($time + 3600) < time()) {
            return $this->error('邀请已失效');
        }
        $bookInfo = ViwUserBookModel::instance()->where('book_id', $book_id)->where('uid', $book_uid)->where('status', 1)->where('admin', 1)->find();
        if (!$bookInfo) {
            return $this->error('未知邀请信息');
        }

        return $this->success('ok', [
            'book_id'   => $book_id,
            'title'     => $bookInfo['title'],
            'nickname'  => $bookInfo['nickname'],
        ]);
    }

    /**
     * 加入账本
     *
     * @param Request $request  请求实例
     * @param string $code      邀请码
     * @return Response
     */
    public function attend(Request $request, string $code): Response
    {
        $decryption = decryption($code, $this->apply_salt);
        if (!$decryption) {
            return $this->error('params faild');
        }
        list($time, $book_id, $book_uid) = explode('_', $decryption, 3);
        if (!check('int', $time) || ($time + 3600) < time()) {
            return $this->error('邀请已失效');
        }
        $bookInfo = ViwUserBookModel::instance()->where('book_id', $book_id)->where('uid', $book_uid)->where('status', 1)->where('admin', 1)->find();
        if (!$bookInfo) {
            return $this->error('未知邀请信息');
        }
        if ($request->uid == $bookInfo['uid']) {
            // 账本所有人，直接返回
            return $this->success('ok', [
                'id'    => $book_id,
                'title' => $bookInfo['title'],
                'uid'   => $bookInfo['uid'],
                'admin' => 1,
            ]);
        }
        // 加入
        $attend = ConsumptionBookAccessModel::instance()->attend((int)$book_id, $request->uid);
        if (!$attend) {
            return $this->error(ConsumptionBookAccessModel::instance()->getError());
        }

        // 返回账本主要信息
        return $this->success('ok', [
            'id'    => $book_id,
            'title' => $bookInfo['title'],
            'uid'   => $bookInfo['uid'],
            'admin' => 0,
        ]);
    }
}
