<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\orm\db\Raw;
use mon\util\Instance;

/**
 * 分类表模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ConsumptionCateModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'consumption_cate';

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
     * 获取分类
     *
     * @param integer $uid 用户ID
     * @param integer $type 类型
     * @param boolean $custom 是否只获取自定义分类
     * @return array
     */
    public function getUserCate(int $uid, int $type = 0, bool $custom = true): array
    {
        $query = $this->field(['id', 'title', 'icon'])->where('status', 1);
        if ($custom) {
            $query->where('uid', $uid);
        } else {
            $query->where(new Raw('`uid` = ' . $uid . ' OR `uid` = 0'));
        }
        if ($type > 0) {
            $query->where('type', $type);
        }
        return $query->select();
    }

    /**
     * 添加分类
     *
     * @param array $option 分类参数
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function add(array $option, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'type'      => ['required', 'in:1,2,3'],
            'title'     => ['required', 'str', 'maxLength:4'],
            'icon'      => ['required', 'str'],
        ])->message([
            'type'      => '请选择类型',
            'title'     => '请输入合法的名称',
            'icon'      => '请选择图标',
        ])->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $save = $this->save([
            'uid'   => $uid,
            'type'  => $option['type'],
            'title' => $option['title'],
            'icon'  => $option['icon'],
        ]);
        if (!$save) {
            $this->error = '添加分类失败';
            return false;
        }

        return true;
    }

    /**
     * 编辑
     *
     * @param array $option 分类参数
     * @param integer $idx  分类ID
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function edit(array $option, int $idx, int $uid): bool
    {
        // 校验数据
        $check = $this->validate()->data($option)->rule([
            'type'      => ['required', 'in:1,2,3'],
            'title'     => ['required', 'str', 'maxLength:4'],
            'icon'      => ['required', 'str'],
        ])->message([
            'type'      => '请选择类型',
            'title'     => '请输入合法的名称',
            'icon'      => '请选择图标',
        ])->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $idx)->where('uid', $uid)->where('status', 1)->find();
        if (!$info) {
            $this->error = '分类不存在';
            return false;
        }

        $save = $this->save([
            'type'  => $option['type'],
            'title' => $option['title'],
            'icon'  => $option['icon'],
        ], ['id' => $idx]);
        if (!$save) {
            $this->error = '更改分类信息失败';
            return false;
        }

        return true;
    }

    /**
     * 删除分类
     *
     * @param integer $idx  分类ID
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function remove(int $idx, int $uid): bool
    {
        $info = $this->where('id', $idx)->where('uid', $uid)->where('status', 1)->find();
        if (!$info) {
            $this->error = '分类不存在';
            return false;
        }

        $save = $this->save(['status' => 0], ['id' => $idx]);
        if (!$save) {
            $this->error = '删除分类失败';
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
