<?php


/*
|--------------------------------------------------------------------------
| 错误信息处理
|--------------------------------------------------------------------------
| 这里定义PHP错误处理，开启所有错误信息
|
*/
ini_set('display_errors', 'on');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| 定义应用根路径
|--------------------------------------------------------------------------
| 这里定义根路径, 用于路径查找
|
*/
define('ROOT_PATH', dirname(__DIR__));


/*
|--------------------------------------------------------------------------
| 定义应用APP路径
|--------------------------------------------------------------------------
| 这里定义用户主要编写业务代码存放路径, 用于路径查找
|
*/
define('APP_PATH', ROOT_PATH . '/app');


/*
|--------------------------------------------------------------------------
| 进程驱动目录路径
|--------------------------------------------------------------------------
| 这里定义进程驱动类库存在目录，目录最好拥有读写权限
|
*/
define('PROCESS_PATH', ROOT_PATH . '/process');


/*
|--------------------------------------------------------------------------
| 定义配置文件路径
|--------------------------------------------------------------------------
| 这里定义配置文件路径, 用于路径查找获取配置信息
|
*/
define('CONFIG_PATH', ROOT_PATH . '/config');


/*
|--------------------------------------------------------------------------
| 定义配置文件路径
|--------------------------------------------------------------------------
| 这里定义配置文件路径, 用于路径查找获取配置信息
|
*/
define('ENV_PATH', ROOT_PATH . '/.env');


/*
|--------------------------------------------------------------------------
| 运行时目录路径
|--------------------------------------------------------------------------
| 这里定义运行时路径, 用于存储系统运行时相关资源内容，目录需拥有读写权限
| 如果打包为phar运行，则需要将路径写为固定绝对路径，如：/home/www/gaia/runtime
|
*/
define('RUNTIME_PATH', ROOT_PATH . '/runtime');


/*
|--------------------------------------------------------------------------
| 定义插件存放路径
|--------------------------------------------------------------------------
| 这里定义插件存放路径, 用于获取已经安装的插件
|
*/
define('PLUGIN_PATH', ROOT_PATH . '/plugins');
