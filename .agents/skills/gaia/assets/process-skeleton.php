<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use gaia\interfaces\ProcessInterface;

/**
 * {{NAME}} 进程
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Process extends Process implements ProcessInterface
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static array $processConfig = [
        'listen'     => '{{LISTEN}}',       // 必填，如 'tcp://0.0.0.0:8000'
        'context'    => [],
        'count'      => 1,
        'transport'  => 'tcp',
        'user'       => '',
        'group'      => '',
        'reusePort'  => false,
        'reloadable' => true,
    ];

    /**
     * 进程启动
     *
     * @param Worker $worker
     */
    public function onWorkerStart(Worker $worker): void
    {
        // TODO: 进程启动初始化业务
    }

    /**
     * 进程停止（必须清理资源）
     *
     * @param Worker $worker
     */
    public function onWorkerStop(Worker $worker): void
    {
        // TODO: 清理 Timer、关闭长连接等
    }
}
