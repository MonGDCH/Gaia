<?php
/*
|--------------------------------------------------------------------------
| 应用配置文件
|--------------------------------------------------------------------------
| 定义应用配置信息
|
*/

return [
    // 是否调试模式
    'debug' => env('APP_DEBUG', true),
    // 时区
    'timezone'  => 'PRC',
    // 监控服务
    'monitor'   => [
        // 监控的文件目录
        'paths' => [APP_PATH, CONFIG_PATH, SUPPORT_PATH],
        // 监控指定后缀名文件
        'exts'  => ['php', 'html'],
        // 暂停监控服务锁文件未知
        'lock'  => RUNTIME_PATH . '/monitor.lock',
    ],
    // 框架钩子
    'hooks' => [
        // 应用初始化
        'app_init'      => [],
        // 应用启动
        'app_start'     => [],
        // 应用加载进程
        'app_run'       => [],
        // 基础初始化
        'process_init'  => [],
        // 进程启动
        'process_start' => [],
        // 进程错误
        'process_error' => []
    ],
    // worker进程配置
    'worker' => [
        // 默认的最大可接受数据包大小
        'max_package_size'  => 10 * 1024 * 1024,
        // 存储主进程PID的文件
        'pid_file'          => 'gaia.pid',
        // 存储关闭服务标准输出的文件
        'stdout_file'       => 'stdout.log',
        // workerman日志记录文件
        'log_file'          => 'workerman.log',
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        'status_file'       => 'gaia.status',
        // workerman事件循环使用对象，默认 \Workerman\Events\Select。一般不需要修改，空则可以
        'event_loop_class'  => '',
        // 发送停止命令后，多少秒内程序没有停止，则强制停止
        'stop_timeout'      => 2
    ],
    // phar包配置
    'phar' => [
        // 打包生成phar包路径
        'build_path'        => ROOT_PATH . DIRECTORY_SEPARATOR . 'build',
        // phar包文件名
        'phar_name'         => 'gaia.phar',
        // bin文件名
        'bin_name'          => 'gaia.bin',
        // 签名算法
        'algorithm'         => Phar::SHA256,
        // 基于根目录排除的目录
        'exclude_dirs'      => ['bin', 'build', 'resource', 'public', 'runtime', '.git', '.github', '.idea', '.setting'],
        // 排除路径
        'exclude_paths'     => ['*/test/*', '*/tests/*', '*/example/*', '*/examples/*', '*/demo/*', '*/phpunit/*', '*/.github/*', '*/.git/*', '*/.idea/*', '*/.setting/*'],
        // 排除文件
        'exclude_files'     => ['composer.json', 'composer.lock', 'composer.dev.json', '.env', '.gitignore', '.DS_Store', 'LICENSE', '*.md', '*.example'],
        // 排除文件路径
        'exclude_filePaths' => [],
        // 需要混淆的目录
        'obfuscate_dirs'    => ['app', 'support', 'config'],
        // openSSL私钥文件路径
        'private_key_file'  => '',
        // 排除的文件或目录正则
        'exclude_pattern'   => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.svn/|/.setting/|/runtime/|/vendor-bin/|/build/|/bin/))(.*)$#',
        // 二进制编译运行环境自定义ini配置
        'custom_ini'        => ['memory_limit=256M'],
    ],
];
