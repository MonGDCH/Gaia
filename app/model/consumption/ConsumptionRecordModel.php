<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;
use mon\orm\exception\DbException;

/**
 * 记录表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionRecordModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_record';

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
     * 添加记录
     *
     * @param array $option
     * @param integer $uid
     * @return boolean
     */
    public function add(array $option, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'book_id'   => ['required', 'int', 'min:1'],
            'cate_id'   => ['required', 'int', 'min:1'],
            'type'      => ['required', 'in:1,2,3'],
            'price'     => ['required', 'num', 'min:0'],
            'oper_time' => ['required', 'int', 'min:0'],
            'comment'   => ['str'],
        ])->message([
            'book_id'   => '请选择账本',
            'cate_id'   => '请选择分类',
            'type'      => '请选择收支类型',
            'price'     => '请输入金额',
            'oper_time' => '请选择时间',
            'comment'   => '请输入合法的备注',
        ])->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        $option['uid'] = $uid;

        $this->startTrans();
        try {
            $save = $this->allowField(['book_id', 'cate_id', 'type', 'price', 'oper_time', 'comment', 'uid'])->save($option);
            if (!$save) {
                $this->rollback();
                $this->error = '添加记录失败';
                return false;
            }
            // 判断存在附件图片，则保存附件
            if (isset($option['imgs']) && is_array($option['imgs']) && !empty($option['imgs'])) {
                $record_id = $this->getLastInsID();
                $saveAnnex = ConsumptionRecordAnnexModel::instance()->add((int)$record_id, $option['imgs']);
                if (!$saveAnnex) {
                    $this->rollback();
                    $this->error = ConsumptionRecordAnnexModel::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            // Log::instance()->oss(__FILE__, __LINE__, 'add consumption record exception, msg => ' . $e->getMessage(), 'error');
            $this->error = '添加记录异常';
            return false;
        }
    }

    /**
     * 编辑
     *
     * @param array $option 编辑数据
     * @param integer $idx  记录ID
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function edit(array $option, int $idx, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'book_id'   => ['required', 'int', 'min:1'],
            'cate_id'   => ['required', 'int', 'min:1'],
            'type'      => ['required', 'in:1,2,3'],
            'price'     => ['required',  'num', 'min:0'],
            'oper_time' => ['required', 'int', 'min:0'],
            'comment'   => ['str'],
        ])->message([
            'book_id'   => '请选择账本',
            'cate_id'   => '请选择分类',
            'type'      => '请选择收支类型',
            'price'     => '请输入金额',
            'oper_time' => '请选择时间',
            'comment'   => '请输入合法的备注',
        ])->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $idx)->where('status', 1)->find();
        if (!$info) {
            $this->error = '记录不存在';
            return false;
        }
        // 判断记录是否为当前用户添加，或者当前用户为记录账本对应的管理员，方可删除
        if ($info['uid'] != $uid) {
            $bookInfo = ConsumptionBookModel::instance()->where('id', $info['book_id'])->where('uid', $uid)->where('status', 1)->find();
            if (!$bookInfo) {
                $this->error = '没有权限修改该记录';
                return false;
            }
        }

        $this->startTrans();
        try {
            // 修改保存
            $save = $this->allowField(['cate_id', 'type', 'price', 'oper_time', 'comment'])->save($option, ['id' => $idx]);
            if (!$save) {
                $this->rollback();
                $this->error = '修改记录信息失败';
                return false;
            }
            // 移除原记录附件
            $delAnnex = ConsumptionRecordAnnexModel::instance()->remove($idx);
            if (!$delAnnex) {
                $this->rollback();
                $this->error = ConsumptionRecordAnnexModel::instance()->getError();
                return false;
            }
            // 判断存在附件图片，则保存附件
            if (isset($option['imgs']) && is_array($option['imgs']) && !empty($option['imgs'])) {
                $saveAnnex = ConsumptionRecordAnnexModel::instance()->add($idx, $option['imgs']);
                if (!$saveAnnex) {
                    $this->rollback();
                    $this->error = ConsumptionRecordAnnexModel::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            // Log::instance()->oss(__FILE__, __LINE__, 'edit consumption record exception, msg => ' . $e->getMessage(), 'error');
            $this->error = '添加记录异常';
            return false;
        }
    }

    /**
     * 删除记录
     *
     * @param integer $idx  记录ID
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function remove(int $idx, int $uid): bool
    {
        $info = $this->where('id', $idx)->where('status', 1)->find();
        if (!$info) {
            $this->error = '记录不存在';
            return false;
        }
        // 判断记录是否为当前用户添加，或者当前用户为记录账本对应的管理员，方可删除
        if ($info['uid'] != $uid) {
            $bookInfo = ConsumptionBookModel::instance()->where('id', $info['book_id'])->where('uid', $uid)->where('status', 1)->find();
            if (!$bookInfo) {
                $this->error = '没有权限删除该记录';
                return false;
            }
        }

        $save = $this->save(['status' => 0], ['id' => $idx]);
        if (!$save) {
            $this->error = '删除记录失败';
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
