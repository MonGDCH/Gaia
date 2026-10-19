<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use Workerman\Timer;
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
     * 进程配置（必须无类型声明，与父类 gaia\Process 保持一致，加 array 会 Fatal Error）
     *
     * @var array
     */
    protected static $processConfig = [
        'listen'     => '{{LISTEN}}',       // 必填，如 'tcp://0.0.0.0:8000'；内部进程用 'tcp://0.0.0.0:0'
        'context'    => [],
        'count'      => 1,
        'transport'  => 'tcp',
        'user'       => '',
        'group'      => '',
        'reusePort'  => false,
        'reloadable' => true,
    ];

    /**
     * Timer ID 映射
     *
     * @var array<string, integer>
     */
    protected array $timerIds = [];

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // TODO: 注册 Timer 等初始化
        // $this->timerIds['xxx'] = Timer::add(1, function () { ... });

        // ✅ 进程关闭钩子必须在此注册（禁止定义 onWorkerStop 方法，框架启动时会立即调用同名方法）
        $worker->onWorkerStop = function (Worker $worker) {
            // TODO: 清理 Timer、关闭长连接
            foreach ($this->timerIds as $timerId) {
                Timer::del($timerId);
            }
            $this->timerIds = [];
        };
    }
}
