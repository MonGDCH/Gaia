<?php
declare(strict_types=1);

namespace app\crontab;

use Throwable;
use mon\log\Logger;

/**
 * {{NAME}} 定时任务处理器
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Task
{
    /**
     * 执行定时任务
     *
     * 默认入口方法名为 handler，可在 config/crontab/job.php 中用 `Class@method` 指定其他方法
     *
     * @return mixed
     */
    public function handler()
    {
        try {
            // TODO: 业务逻辑，委托给 Service 处理

            return '{{NAME}} Task runing';
        } catch (Throwable $e) {
            // ✅ 必须捕获，异常外抛会影响调度进程
            Logger::error('{{NAME}} 定时任务执行失败', ['error' => $e->getMessage()]);

            return $e->getMessage();
        }
    }
}
