<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;
use mon\orm\exception\DbException;

/**
 * 账本用户关联表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionBookAccessModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_book_access';

    /**
     * 新增自动写入字段
     *
     * @var array
     */
    protected $insert = ['create_time'];


    /**
     * 加入账本
     *
     * @param integer $book_id 账本ID
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function attend(int $book_id, int $uid): bool
    {
        $info = ConsumptionBookModel::instance()->where('id', $book_id)->where('status', 1)->find();
        if (!$info) {
            $this->error = '账本不存在';
            return false;
        }

        $exists = $this->where('uid', $uid)->where('book_id', $book_id)->find();
        if ($exists) {
            $this->error = '已加入';
            return false;
        }

        $save = $this->save(['uid' => $uid, 'book_id' => $book_id]);
        if (!$save) {
            $this->error = '加入操作失败';
            return false;
        }

        return true;
    }

    /**
     * 移除账本用户
     *
     * @param integer $book_id
     * @param integer $leaveUid
     * @param integer $uid
     * @return boolean
     */
    public function leave(int $book_id, int $leaveUid, int $uid): bool
    {
        // 判断账本存在，且为管理员
        $info = ConsumptionBookModel::instance()->where('id', $book_id)->where('uid', $uid)->where('status', 1)->find();
        if (!$info) {
            $this->error = '账本不存在';
            return false;
        }

        $exists = $this->where('uid', $leaveUid)->where('book_id', $book_id)->find();
        if (!$exists) {
            $this->error = '关联用户不存在';
            return false;
        }

        $del = $this->where('uid', $leaveUid)->where('book_id', $book_id)->delete();
        if (!$del) {
            $this->error = '移除操作失败';
            return false;
        }

        return true;
    }

    /**
     * 退出账本
     *
     * @param integer $book_id 账本ID
     * @param integer $quit_id 移除用户ID
     * @param integer $uid 当前用户ID
     * @return boolean
     */
    public function quit(int $book_id, int $quit_id, int $uid): bool
    {
        $bookInfo = ConsumptionBookModel::instance()->where('id', $book_id)->where('uid', $uid)->find();
        if ($quit_id == $uid) {
            // 当前用户退出
            if ($bookInfo) {
                $this->error = '账本管理员不能退出';
                return false;
            }
        } else {
            // 管理员移除其它用户
            if (!$bookInfo) {
                $this->error = '没有权限移除用户';
                return false;
            }
        }

        // 检查是否已关联
        $exists = $this->where('uid', $quit_id)->where('book_id', $book_id)->find();
        if (!$exists) {
            $this->error = '未关联账本';
            return false;
        }
        // 移除
        $del = $this->where('uid', $quit_id)->where('book_id', $book_id)->delete();
        if (!$del) {
            $this->error = '退出操作失败';
            return false;
        }

        return true;
    }

    /**
     * 重置账本关联用户
     *
     * @param integer $book_id 账本ID
     * @param array $uids 用户用户列表[1,2,3]
     * @return boolean
     */
    public function reset(int $book_id, array $uids): bool
    {
        // 整理写入的数据
        $data = [];
        $time = time();
        foreach ($uids as $uid) {
            $data[] = [
                'uid' => $uid,
                'book_id' => $book_id,
                'create_time' => $time
            ];
        }

        $this->startTrans();
        try {
            $delete = $this->where('book_id', $book_id)->delete();
            if ($delete === false) {
                $this->rollback();
                $this->error = '重置账本失败';
                return false;
            }

            $add = $this->insertAll($data);
            if (!$add) {
                $this->rollback();
                $this->error = '重置账本关联用户失败';
                return false;
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            $this->error = '重置账本关联用户异常';
            // Log::instance()->oss(__FILE__, __LINE__, 'reset book users error, msg => ' . $e->getMessage());
            return false;
        }
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
}
