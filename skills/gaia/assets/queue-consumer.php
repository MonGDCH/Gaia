<?php
declare(strict_types=1);

namespace app\queue;

use Throwable;
use mon\util\Instance;
use mon\log\Logger;
use gaia\queue\ConsumerInterface;
use support\queue\QueueService;

/**
 * {{NAME}} 消息队列消费者
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Queue implements ConsumerInterface
{
    use Instance;

    /**
     * 使用的队列连接配置名，对应 config/queue/queue.php 的 connections
     *
     * @return string
     */
    public function connection(): string
    {
        return 'default';
    }

    /**
     * 监听的队列名
     *
     * @return string
     */
    public function queue(): string
    {
        return '{{QUEUE_NAME}}';
    }

    /**
     * 队列描述信息
     *
     * @return string
     */
    public function describe(): string
    {
        return '{{DESCRIBE}}';
    }

    /**
     * 消费处理
     *
     * @param mixed $data 消息数据
     * @return mixed
     */
    public function consume($data)
    {
        try {
            // TODO: 业务逻辑，委托给 Service 处理
            Logger::info('{{NAME}} 队列消费成功', ['data' => $data]);

            return true;
        } catch (Throwable $e) {
            // ✅ 必须捕获，异常外抛会触发失败重试
            Logger::error('{{NAME}} 队列消费失败', [
                'queue' => $this->queue(),
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 投递消息（便捷方法，可选）
     *
     * @param array $data   消息数据
     * @param integer $delay 延迟执行秒数
     * @return boolean
     */
    public function sendQuery(array $data = [], int $delay = 0): bool
    {
        return QueueService::syncSend($this->queue(), $data, $delay, $this->connection());
    }

    /**
     * 消费失败回调（可选）
     *
     * @param Throwable $e
     * @param array $package
     * @return mixed
     */
    public function onConsumeFailure(Throwable $e, array $package)
    {
        Logger::error('队列消费失败', [
            'queue' => $package['queue'] ?? '',
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 消费成功回调（可选）
     *
     * @param mixed $result
     * @param array $package
     * @return void
     */
    public function onConsumeSuccess($result, array $package)
    {
        Logger::debug('队列消费成功', ['queue' => $package['queue'] ?? '']);
    }
}
