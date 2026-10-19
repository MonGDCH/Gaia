<?php
declare(strict_types=1);

namespace app\command;

use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * {{NAME}} 指令
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Command extends Command
{
    protected static string $defaultName = '{{DEFAULT_NAME}}';
    protected static string $defaultDescription = '{{DESCRIPTION}}';
    protected static string $defaultGroup = 'app';

    /**
     * 执行指令
     *
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function execute(Input $input, Output $output): int
    {
        // 委托给 Service，不要直接写业务逻辑
        return $output->write('Done.');
    }
}
