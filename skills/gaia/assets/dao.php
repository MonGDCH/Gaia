<?php

declare(strict_types=1);

namespace app\dao\{{MODULE}};

use mon\thinkORM\Dao;
use mon\util\Instance;
use mon\thinkORM\extend\Query;
use app\validate\{{MODULE}}\{{NAME}}Validate;

/**
 * {{DESC}} 数据访问对象
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Dao extends Dao
{
    use Instance;

    /**
     * 操作表名（不含前缀）
     *
     * @var string
     */
    protected $table = '{{TABLE}}';

    /**
     * 连接的数据库配置节点，空则使用默认连接
     *
     * @var string
     */
    protected $connection = '';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 验证器
     *
     * @var string
     */
    protected $validate = {{NAME}}Validate::class;

    /**
     * 只允许操作的字段
     *
     * @var array
     */
    protected $allow = [];

    /**
     * 查询条件构造（列表/导出复用）
     *
     * @param array $data 筛选条件
     * @return Query
     */
    public function queryScope(array $data = []): Query
    {
        $query = $this->db();
        // 主键
        if (isset($data['id']) && $this->validate()->int($data['id'])) {
            $query->where('id', intval($data['id']));
        }
        // 状态
        if (isset($data['status']) && $this->validate()->int($data['status'])) {
            $query->where('status', intval($data['status']));
        }

        return $query->order('id', 'desc');
    }
}
