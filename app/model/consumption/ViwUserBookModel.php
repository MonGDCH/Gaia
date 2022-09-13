<?php

declare(strict_types=1);

namespace app\model\consumption;

use mon\orm\Model;
use mon\util\Instance;

/**
 * 用户账本视图模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ViwUserBookModel extends Model
{
    use Instance;

    /**
     * 视图名
     *
     * @var string
     */
    protected $table = 'viw_user_book';
}
