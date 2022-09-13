<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;
use mon\orm\exception\DbException;

/**
 * 记录附件表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionRecordAnnexModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_record_annex';

    /**
     * 新增自动写入字段
     *
     * @var array
     */
    protected $insert = ['create_time'];

    /**
     * 更新附件信息
     *
     * @param integer $record_id 记录ID
     * @param array $assets 资源路径列表
     * @return boolean
     */
    public function modify(int $record_id, array $assets): bool
    {
        $this->startTrans();
        try {
            // 删除原记录
            $delete = $this->remove($record_id);
            if (!$delete) {
                $this->rollback();
                return false;
            }
            // 添加新记录
            $save = $this->add($record_id, $assets);
            if (!$save) {
                $this->rollback();
                return false;
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            // Log::instance()->oss(__FILE__, __LINE__, 'modify consumption record annex exception, msg => ' . $e->getMessage(), 'error');
            $this->error = '更新附件信息异常';
            return false;
        }
    }

    /**
     * 记录附件
     *
     * @param integer $record_id 记录ID
     * @param array $assets 资源路径列表
     * @return boolean
     */
    public function add(int $record_id, array $assets): bool
    {
        $insert = [];
        foreach ($assets as $url) {
            $insert[] = [
                'record_id' => $record_id,
                'assets'    => $url,
            ];
        }

        $save = $this->saveAll($insert);
        if (!$save) {
            $this->error = '记录附件信息失败';
            return false;
        }

        return true;
    }

    /**
     * 删除附件
     *
     * @param integer $record_id 记录ID
     * @return boolean
     */
    public function remove(int $record_id): bool
    {
        $delete = $this->where('record_id', $record_id)->delete();
        if ($delete === false) {
            $this->error = '删除附件失败';
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
}
