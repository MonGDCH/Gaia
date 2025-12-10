<?php

declare(strict_types=1);

namespace app\command;

use gaia\Gaia;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * app 指令
 *
 * Class App
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 2025-12-10
 */
class AppCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'app';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Start the App service.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'app';

    /**
     * 应用名称
     *
     * @var string
     */
    protected $name = 'app';

    /**
     * 启动进程
     *
     * @example 进程名 => 进程驱动类名, eg: ['test' => Test::class]
     * @var array
     */
    protected $process = [];

    /**
     * 开启插件支持
     *
     * @var boolean
     */
    protected $supportPlugin = true;

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        if (empty($this->process)) {
            return $output->block('未定义启动进程!', 'error');
        }
        if (empty($this->name)) {
            return $output->block('未定义应用名称!', 'error');
        }

        // TODO 更多操作

        // 启动服务
        Gaia::instance()->runProcess($this->process);
    }
}
