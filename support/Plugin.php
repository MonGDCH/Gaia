<?php

namespace support;

use mon\util\File;
use mon\env\Config;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use gaia\interfaces\PluginInterface;

/**
 * 插件安装驱动
 * 
 * @see 修改自webman/plugin
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Plugin
{
    /**
     * 安装
     *
     * @param mixed $event
     * @return void
     */
    public static function install($event)
    {
        static::callPlugin($event, 'install');
    }

    /**
     * 更新
     *
     * @param mixed $event
     * @return void
     */
    public static function update($event)
    {
        static::callPlugin($event, 'update');
    }

    /**
     * 卸载
     *
     * @param mixed $event
     * @return void
     */
    public static function uninstall($event)
    {
        static::callPlugin($event, 'uninstall');
    }

    /**
     * 获取请求根路径
     *
     * @return string
     */
    public static function getRootPath(): string
    {
        return defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    }

    /**
     * 注册启用插件
     *
     * @param string $path  插件路径，默认常量 PLUGIN_PATH
     * @return void
     */
    public static function register(string $path = '')
    {
        // 插件路径
        $path = $path ?: PLUGIN_PATH;
        if (!is_dir($path)) {
            return;
        }
        // 加载插件
        $plugins = glob($path . '/**/Bootstrap.php');
        foreach ($plugins as $plugin) {
            // 插件路径
            $plugin_path = dirname($plugin);
            // 插件名
            $plugin_name = basename($plugin_path);
            $className = '\\plugins\\' . $plugin_name . '\\Bootstrap';
            if (!class_exists($className) || !is_subclass_of($className, PluginInterface::class)) {
                // 跳过非插件目录
                continue;
            }
            // 插件是否启用
            if (!$className::enable()) {
                // 跳过未启用插件
                continue;
            }

            // 配置路径
            $config_path = $plugin_path . DIRECTORY_SEPARATOR . 'config';
            // 加载配置
            $config = [];
            if (is_dir($config_path)) {
                $config = Config::instance()->loadDir($config_path, true, [], 'plugins.' . $plugin_name);
            }
            // 初始化
            $className::init($config);
        }
    }

    /**
     * 复制文件夹
     *
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     * @param boolean $overwrite   文件是否覆盖，默认不覆盖
     * @return void
     */
    public static function copydir($source, $dest, $overwrite = false)
    {
        $dest = static::getRootPath() . DIRECTORY_SEPARATOR . $dest;
        File::instance()->createDir($dest);
        echo "Create Dir $dest\r\n";
        $dir_iterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . '/' . $iterator->getSubPathName();
                File::instance()->createDir($sontDir);
                echo "Create Dir $sontDir\r\n";
            } else {
                $file = $dest . '/' . $iterator->getSubPathName();
                if (file_exists($file) && !$overwrite) {
                    continue;
                }

                copy($item, $file);
                echo "Create File $file\r\n";
            }
        }
    }

    /**
     * 复制文件
     *
     * @param string $source    源文件
     * @param string $dest  目标文件
     * @param boolean $overwrite    文件是否覆盖，默认不覆盖
     * @return void
     */
    public static function copyFile($source, $dest, $overwrite = false)
    {
        $dest = static::getRootPath() . DIRECTORY_SEPARATOR . $dest;
        File::instance()->copyFile($source, $dest, $overwrite);
        echo "Create File $dest\r\n";
    }

    /**
     * 执行插件方法
     *
     * @param mixed $event  composer事件实例
     * @param string $call  插件方法名
     * @return void
     */
    protected static function callPlugin($event, string $call)
    {
        static::init();
        $namespace = static::getNamespace($event);
        if (is_null($namespace)) {
            return;
        }
        $call_function = "\\{$namespace}Install::{$call}";
        if (static::checkPlugin($namespace) && is_callable($call_function)) {
            $call_function();
        }
    }

    /**
     * 是否为webman或者gaia的插件
     *
     * @param string $namespace
     * @return boolean
     */
    protected static function checkPlugin($namespace)
    {
        $gaia = "\\{$namespace}Install::GAIA_PLUGIN";
        return defined($gaia);
    }

    /**
     * 获取命名空间
     *
     * @param mixed $event
     * @return string|null
     */
    protected static function getNamespace($event)
    {
        $operation = $event->getOperation();
        $autoload = method_exists($operation, 'getPackage') ? $operation->getPackage()->getAutoload() : $operation->getTargetPackage()->getAutoload();
        if (!isset($autoload['psr-4'])) {
            return null;
        }

        return key($autoload['psr-4']);
    }

    /**
     * 初始化
     *
     * @return void
     */
    protected static function init()
    {
        // Plugin.php in vendor
        $file = __DIR__ . '/../../../../../support/bootstrap.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
        require_once __DIR__ . '/bootstrap.php';
    }
}
