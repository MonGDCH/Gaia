<?php

declare(strict_types=1);

namespace support\process;

use gaia\Process;
use Workerman\Worker;
use gaia\interfaces\ProcessInterface;

/**
 * test 进程
 *
 * Class Test
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 2024-06-25
 */
class Test extends Process implements ProcessInterface
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        // 监听协议端口
        'listen'        => '',
        // 额外参数
        'context'       => [],
        // 进程数
        'count'         => 1,
        // 通信协议，一般不需要修改
        'transport'     => 'tcp',
        // 进程用户，一般不需要修改
        'user'          => '',
        // 进程用户组，一般不需要修改
        'group'         => '',
        // 是否开启端口复用
        'reusePort'     => false,
        // 是否允许进程重载
        'reloadable'    => true,
    ];

    /**
     * 进程启动
     *
     * @param Worker $worker worker进程
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 进程启动初始化业务
    }
}