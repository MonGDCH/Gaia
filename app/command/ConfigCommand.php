<?php

declare(strict_types=1);

namespace app\command;

use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use mon\env\Config as Configuration;

/**
 * 查看配置
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class ConfigCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'config:get';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Config utils';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'app';

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        // 获取查看的节点
        $args = $in->getArgs();
        $action = $args[0] ?? '';
        $out->write('');
        $config = Configuration::instance()->get($action, []);


        if (!empty($action)) {
            return $out->dataList((array)$config, $action, false, ['ucFirst' => false]);
        } else {
            foreach ($config as $title => $value) {
                $out->dataList($value, $title, false, ['ucFirst' => false]);
            }

            return 0;
        }
    }
}
