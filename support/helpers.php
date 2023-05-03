<?php
/*
|--------------------------------------------------------------------------
| 应用公共函数定义文件
|--------------------------------------------------------------------------
| 定义公用的全局函数
|
*/

if (!function_exists('env')) {
    /**
     * 获取环境变量配置信息
     *
     * @param string $key   配置名
     * @param mixed $default    默认值
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return \mon\env\Env::get($key, $default);
    }
}
