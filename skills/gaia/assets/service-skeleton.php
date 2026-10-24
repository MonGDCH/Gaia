<?php
declare(strict_types=1);

namespace app\service;

use mon\util\Instance;

/**
 * {{NAME}}Service 一句话描述
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Service
{
    use Instance;

    /**
     * 配置信息
     *
     * @var array
     */
    protected array $config = [];

    /**
     * 构造方法
     */
    protected function __construct()
    {
        $this->config = array_merge($this->config, \mon\env\Config::instance()->get('{{CONFIG_KEY}}', []));
    }
}
