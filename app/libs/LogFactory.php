<?php

declare(strict_types=1);

namespace app\libs;

use mon\util\Log;
use ErrorException;
use mon\util\Instance;

/**
 * 日志工厂
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LogFactory
{
    use Instance;

    /**
     * 日志记录通道
     *
     * @var array
     */
    protected $channels = [];

    /**
     * 加载通道
     *
     * @param array $configs 批量通道配置
     * @return LogFactory
     */
    public function loadChannel(array $configs): LogFactory
    {
        foreach ($configs as $name => $config) {
            $this->createChannel($name, $config);
        }

        return $this;
    }

    /**
     * 创建通道
     *
     * @param string $name  通道名称
     * @param array $config 通道配置
     * @return LogFactory
     */
    public function createChannel(string $name, array $config = []): LogFactory
    {
        $this->channels[$name] = new Log($config);
        return $this;
    }

    /**
     * 是否存在指定通道
     *
     * @param string $name  通道名称
     * @return boolean
     */
    public function hasChannel(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * 获取通道
     *
     * @param string $name  通道名称
     * @return Log
     */
    public function channel(string $name): Log
    {
        if (!isset($this->channels[$name])) {
            throw new ErrorException('Log channel [' . $name . '] not found!');
        }

        return $this->channels[$name];
    }
}
