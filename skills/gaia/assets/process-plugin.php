<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use Workerman\Timer;
use mon\log\Logger;
use gaia\interfaces\ProcessInterface;

/**
 * {{NAME}} 进程（集成 ORM / 缓存等插件能力的示例）
 *
 * 使用插件能力的自定义进程模板：
 * 1. onWorkerStart 中完成插件初始化（ORM::register(true) 等）
 * 2. 注册 Timer 后必须保存 ID
 * 3. 清理逻辑写在 $worker->onWorkerStop 闭包中，禁止定义 onWorkerStop 方法
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
        'listen'     => 'tcp://0.0.0.0:0',   // 内部进程无需监听端口
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
        Logger::info('{{NAME}} 进程启动', ['worker_id' => $worker->id]);

        // 注册 ORM（常驻内存必须，第二个参数 true 开启长连接心跳保活）
        if (class_exists(\mon\thinkORM\ORM::class)) {
            \mon\thinkORM\ORM::register(true);
        }

        // TODO: 注册业务定时器
        // $this->timerIds['worker'] = Timer::add(1, function () { $this->handle(); });

        // ✅ 进程关闭钩子必须在此注册（禁止定义 onWorkerStop 方法）
        $worker->onWorkerStop = function (Worker $worker) {
            Logger::info('{{NAME}} 进程停止', ['worker_id' => $worker->id]);

            foreach ($this->timerIds as $timerId) {
                Timer::del($timerId);
            }
            $this->timerIds = [];
        };
    }

    /**
     * 业务处理
     *
     * @return void
     */
    protected function handle(): void
    {
        try {
            // TODO: 业务逻辑，委托给 Service 处理
        } catch (\Throwable $e) {
            Logger::error('{{NAME}} 处理失败', ['error' => $e->getMessage()]);
        }
    }
}
