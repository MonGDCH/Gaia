<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;

/**
 * 财报订阅模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionSubscribeModel extends Model
{
    use Instance;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'consumption_subscribe';

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
     * 获取订阅信息
     *
     * @param integer $uid  用户ID
     * @return array
     */
    public function getInfo(int $uid): array
    {
        $info = $this->where('uid', $uid)->field(['email', 'sub_week', 'sub_month'])->find();
        if (!$info) {
            $info = [
                'email'     => '',
                'sub_week'  => 0,
                'sub_month' => 0
            ];
        }

        return $info;
    }

    /**
     * 订阅
     *
     * @param array $option 订阅参数
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function subscribe(array $option, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'email'     => ['required', 'email'],
            'sub_week'  => ['required', 'in:0,1'],
            'sub_month' => ['required', 'in:0,1'],
        ])->message([
            'email'     => '请输入合法的邮箱地址',
            'sub_week'  => '订阅周报参数错误',
            'sub_month' => '订阅月报参数错误',
        ])->check();
        if ($check !== true) {
            $this->error = $this->validate()->getError();
            return false;
        }
        $exists = $this->where('uid', $uid)->find();
        if ($exists) {
            // 存在数据，更新
            $save = $this->allowField(['email', 'sub_week', 'sub_month'])->save($option, ['uid' => $uid]);
            if (!$save) {
                $this->error = '保存记录信息失败';
                return false;
            }
        } else {
            // 不存在，新增
            $option['uid'] = $uid;
            $save = $this->allowField(['email', 'sub_week', 'sub_month', 'uid'])->save($option);
            if (!$save) {
                $this->error = '记录信息失败';
                return false;
            }
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
