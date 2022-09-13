<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;
use mon\orm\exception\DbException;

/**
 * 加入账本申请表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionBookAttendModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_book_attend';

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
     * 申请加入账本
     *
     * @param integer $book_id 账本ID
     * @param integer $uid 申请用户ID
     * @return boolean
     */
    public function apply(int $book_id, int $uid, string $commnet = ''): bool
    {
        $bookInfo = ConsumptionBookModel::instance()->where('id', $book_id)->where('status', 1)->find();
        if (!$bookInfo) {
            $this->error = '账本不存在';
            return false;
        }

        $save = $this->save([
            'book_id' => $book_id,
            'book_uid' => $bookInfo['uid'],
            'attend_uid' => $uid,
            'comment' =>  $commnet
        ]);
        if (!$save) {
            $this->error = '申请失败';
            return false;
        }

        return true;
    }

    /**
     * 审核加入账本编辑
     *
     * @param integer $idx 申请ID
     * @param integer $uid 用户ID
     * @param boolean $check 审核是否通过
     * @return boolean
     */
    public function examine(int $idx, int $uid, bool $check): bool
    {
        $info = $this->where('id', $idx)->where('status', 0)->where('book_uid', $uid)->find();
        if (!$info) {
            $this->error = '申请信息不存在';
            return false;
        }

        $this->startTrans();
        try {
            // 审核通过
            if ($check) {
                $attend = ConsumptionBookAccessModel::instance()->attend($idx, $uid);
                if (!$attend) {
                    $this->rollback();
                    $this->error = ConsumptionBookAccessModel::instance()->getError();
                    return false;
                }
            }

            $save = $this->save(['status' => ($check ? 1 : 2)], ['id' => $idx]);
            if (!$save) {
                $this->rollback();
                $this->error = '操作失败';
                return false;
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            $this->error = '审核加入申请异常';
            // Log::instance()->oss(__FILE__, __LINE__, 'Examine user join book error! msg => ' . $e->getMessage());
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
