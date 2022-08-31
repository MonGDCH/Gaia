<?php

declare(strict_types=1);

namespace app\model;

use mon\orm\Model;
use mon\util\Instance;

/**
 * 用户表模型
 */
class UserModel extends Model
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user';


    /**
     * 获取用户
     *
     * @param array $where 查询条件
     * @return array
     */
    public function getUser(array $where = []): array
    {
        return $this->where($where)->select();
    }
}
