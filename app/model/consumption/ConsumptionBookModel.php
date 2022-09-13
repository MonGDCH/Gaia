<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;
use mon\orm\exception\DbException;

/**
 * 账本表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionBookModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_book';

    /**
     * 新增自动写入字段
     *
     * @var array
     */
    protected $insert = ['create_time', 'update_time'];

    /**
     * 更新自动写入字段
     *
     * @var array
     */
    protected $update = ['update_time'];

    /**
     * 添加账本
     *
     * @param string $title 账本名称
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function add(string $title, int $uid): bool
    {
        $save = $this->save(['uid' => $uid, 'title' => $title]);
        if (!$save) {
            $this->error = '添加账本失败';
            return false;
        }

        return true;
    }

    /**
     * 编辑账本
     *
     * @param integer $idx 账本ID
     * @param array $option 编辑信息
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function modify(int $idx, array $option, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'title' => ['required', 'str', 'minLength:1', 'maxLength:10'],
            'uids'  => ['arr'],
        ])->message([
            'title' => '请输入账本名称',
            'uids'  => '关联用户参数异常',
        ])->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        // 校验uids
        $option['uids'] = isset($option['uids']) ? $option['uids'] : [];
        foreach ($option['uids'] as $i) {
            if (!check('int', $i) || $i < 1) {
                $this->error = '用户参数无效';
                return false;
            }
        }

        // 获取账本信息
        $bookInfo = $this->where('id', $idx)->where('uid', $uid)->where('status', 1)->find();
        if (!$bookInfo) {
            $this->error = '获取账本信息失败';
            return false;
        }
        // 获取关联信息
        $accessList = ConsumptionBookAccessModel::instance()->where('book_id', $idx)->field('uid')->select();
        $uidsList = [];
        foreach ($accessList as $item) {
            $uidsList[] = $item['uid'];
        }
        // 获取更新的关联用户交集
        $uids = array_intersect($uidsList, $option['uids']);

        $this->startTrans();
        try {
            $save = $this->save(['title' => $option['title']], ['id' => $idx]);
            if (!$save) {
                $this->rollback();
                $this->error = '修改账本信息失败';
                return false;
            }

            // 判断是否修改了关联用户
            if (!empty(array_diff($uids, $uidsList))) {
                $reset = ConsumptionBookAccessModel::instance()->reset($idx, $uids);
                if (!$reset) {
                    $this->rollback();
                    $this->error = ConsumptionBookAccessModel::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            $this->error = '编辑账本信息异常';
            // Log::instance()->oss(__FILE__, __LINE__, 'modify book exception, error => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 修改账本名称
     *
     * @param string $title 账本名称
     * @param integer $idx 账本ID
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function edit(int $idx, string $title, int $uid): bool
    {
        $info = $this->where('id', $idx)->where('uid', $uid)->where('status', 1)->find();
        if (!$info) {
            $this->error = '获取账本信息失败';
            return false;
        }

        $save = $this->save(['title' => $title], ['id' => $idx]);
        if (!$save) {
            $this->error = '修改账本信息失败';
            return false;
        }

        return true;
    }

    /**
     * 删除账本
     *
     * @param integer $idx 账本ID
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function remove(int $idx, int $uid): bool
    {
        $info = $this->where('id', $idx)->where('uid', $uid)->where('status', 1)->find();
        if (!$info) {
            $this->error = '账本不存在';
            return false;
        }

        $save = $this->save(['status' => 0], ['id' => $idx]);
        if (!$save) {
            $this->error = '删除账本失败';
            return false;
        }

        return true;
    }

    /**
     * 自动完成create_time字段
     *
     * @return integer
     */
    protected function setCreateTimeAttr()
    {
        return time();
    }

    /**
     * 自动完成update_time字段
     *
     * @return integer
     */
    protected function setUpdateTimeAttr()
    {
        return time();
    }
}
