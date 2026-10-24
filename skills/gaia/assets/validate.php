<?php

declare(strict_types=1);

namespace app\validate\{{MODULE}};

use mon\util\Validate;

/**
 * {{DESC}} 验证器
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Validate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'id'     => ['required', 'id'],
        'name'   => ['required', 'str', 'length:1,50'],
        'status' => ['required', 'in:0,1'],
    ];

    /**
     * 错误提示
     *
     * @var array
     */
    public $message = [
        'id'     => '参数异常',
        'name'   => '名称格式错误',
        'status' => '状态值错误',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增
        'add'  => ['name', 'status'],
        // 编辑
        'edit' => ['id', 'name', 'status'],
    ];
}
