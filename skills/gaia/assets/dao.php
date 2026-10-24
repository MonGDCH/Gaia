<?php
declare(strict_types=1);

namespace app\dao;

use mon\thinkORM\Dao;

/**
 * {{NAME}} Dao
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Dao extends Dao
{
    /**
     * 操作的表名
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
     * 允许操作的字段白名单
     *
     * @var array
     */
    protected $allow = [
        // '{{FIELD}}',
    ];
}
