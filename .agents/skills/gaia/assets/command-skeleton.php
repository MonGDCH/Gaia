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
    // ⚠️ 必须无类型！父类 mon\console\Command 的同名属性均为无类型声明，加 string 会 Fatal Error
    protected static $defaultName = '{{DEFAULT_NAME}}';
    protected static $defaultDescription = '{{DESCRIPTION}}';
    protected static $defaultGroup = 'app';

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
