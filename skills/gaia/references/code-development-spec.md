# Gaia 项目代码开发规范

> 版本：v1.0.0  |  PHP 8.4  |  Workerman  |  PSR-1/2/4/12
> 目标读者：开发人员、AI Agent、Code Reviewer
> 核心原则：每条规则必须**可判定、可执行、可复制**

---

## 目录

1. [项目目录结构图](#1-项目目录结构图)
2. [命名规范](#2-命名规范)
3. [代码结构规范](#3-代码结构规范)
4. [注释与文档规范](#4-注释与文档规范)
5. [异常处理与日志规范](#5-异常处理与日志规范)
6. [代码提交规范](#6-代码提交规范)
7. [Code Review Checklist](#7-code-review-checklist)

---

## 1. 项目目录结构图

### 1.1 当前目录结构

```
gaia/                                  ← 项目根目录
├── app/                               ← 业务代码目录
│   ├── command/                       ← 自定义 CLI 指令（mon-console Command）
│   │   └── AppCommand.php             ← 示例指令
│   ├── process/                       ← 自定义进程驱动类（gaia\Process）
│   │   └── .gitignore                 ← 空目录占位
│   ├── service/                       ← 业务服务层（use Instance 单例）
│   │   ├── LockService.php            ← 分布式锁服务
│   │   ├── MailerService.php          ← 邮件服务
│   │   └── RedisService.php           ← Redis 操作服务
│   └── helpers.php                    ← 全局辅助函数（env/config 等）
│
├── bin/                               ← Windows 进程启动脚本（运行时生成）
│   └── .gitignore
│
├── config/                            ← 应用配置文件（return [] 格式）
│   ├── app.php                        ← 应用主配置（debug/timezone/hooks/worker/phar）
│   ├── email.php                      ← 邮件配置
│   ├── log.php                        ← 日志通道配置
│   └── redis.php                      ← Redis 连接配置
│
├── runtime/                           ← 运行时目录（可写，自动生成）
│   └── .gitignore
│
├── support/                           ← 框架支撑层（插件/引导）
│   ├── bootstrap.php                  ← 常量定义（BASE_PATH/ROOT_PATH/APP_PATH...）
│   └── Plugin.php                     ← Composer 插件驱动（install/update/uninstall）
│
├── vendor/                            ← Composer 依赖（勿改）
├── gaia                               ← CLI 入口脚本（#!/usr/bin/env php）
├── composer.json                      ← composer 配置
├── composer.dev.json                  ← composer 开发环境配置
├── .env.example                       ← 环境变量模板
├── .gitignore
└── README.md
```

### 1.2 目录角色说明【强制】

| 目录 | 角色 | 命名空间 | 可否新增文件 |
|------|------|----------|-------------|
| `app/command/` | CLI 指令 | `app\command\` | ✅ 可新增 |
| `app/process/` | Workerman 进程驱动 | `app\process\` | ✅ 可新增 |
| `app/service/` | 业务服务（单例） | `app\service\` | ✅ 可新增 |
| `config/` | 配置文件 | — (return []) | ✅ 可新增 |
| `support/` | 框架支撑/插件 | `support\` | ⚠️ 仅插件目录 |
| `bin/` | 运行时脚本 | — | ❌ 勿手动修改 |
| `runtime/` | 运行时产物 | — | ❌ 勿手动修改 |
| `vendor/` | 第三方依赖 | — | ❌ 勿修改 |

> **命名空间映射**：composer.json 配置 `"": "./"` 作为 PSR-4 根，即文件路径 `app/service/RedisService.php` 对应完全限定名 `app\service\RedisService`。

### 1.3 目录新增规则【强制】

1. **禁止**在 `app/` 下直接放 `.php` 文件，必须归入 `command/`、`process/`、`service/` 之一。
2. 新增目录需同时更新本节目录结构图。
3. 若未来引入 HTTP 协议或数据库层，目录扩展方案标注「待补充」。

---

## 1.4 CLI 指令与启动流程【强制】

Gaia 通过项目根目录的 `gaia` 脚本作为统一 CLI 入口。所有开发、启动、构建操作均通过此脚本完成。

### 1.4.1 查看可用指令

```bash
# 列出所有指令（框架内置 + 应用自定义）
php gaia
```

实际输出示例：

```
Usage:
php gaia {{command}} [--opt -v -h ...] [arg0 arg1 arg2=value2 ...]

- Gaia Commands:
  build:bin           Build binary file a project
  build:phar          Build packaged a project into phar
  config:get          Config utils
  make:bin            Make process start file util.
  make:cmd            Make command file util.
  make:controller     Make app controller file util.
  make:dao            Make app dao file util.
  make:process        Make process file util.
  vendor:publish      Publish vendor plugins.
  version             Show Gaia Version (alias: v)

- App Commands:
  app     Start the App service.
```

### 1.4.2 指令分类

| 分类 | 前缀 | 来源 | 说明 |
|------|------|------|------|
| **Gaia Commands** | `gaia\command\` | gaia-framework 框架内置 | 脚手架、构建、配置工具 |
| **App Commands** | `app\command\` | 项目自定义 | 启动应用、业务指令 |

### 1.4.3 框架内置指令速查

| 指令 | 用途 | 示例 |
|------|------|------|
| `php gaia make:process <name>` | 生成进程驱动类到 `app/process/` | `php gaia make:process HttpWorker` |
| `php gaia make:cmd <name>` | 生成 CLI 指令类到 `app/command/` | `php gaia make:cmd CleanCache` |
| `php gaia make:controller <name>` | 生成 HTTP 控制器 | 标注「待补充」（当前无 HTTP 场景） |
| `php gaia make:dao <name>` | 生成 DAO 类 | 标注「待补充」（当前无数据库层） |
| `php gaia make:bin <name>` | 生成进程启动脚本 | `php gaia make:bin http` |
| `php gaia build:phar` | 打包项目为 phar 文件 | `php gaia build:phar` |
| `php gaia build:bin` | 编译为二进制可执行文件 | `php gaia build:bin` |
| `php gaia config:get [key]` | 查看运行时配置 | `php gaia config:get redis` / `php gaia config:get` |
| `php gaia vendor:publish` | 发布 vendor 插件资源 | `php gaia vendor:publish` |
| `php gaia version` / `php gaia v` | 显示 Gaia 版本 | `php gaia v` |

### 1.4.4 启动应用完整流程【强制】

**第 1 步：生成进程驱动类**

```bash
php gaia make:process Worker  # 生成 app/process/Worker.php
```

生成的模板代码（`gaia\command\MakeProcessCommand` 实际输出）：

```php
<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use gaia\interfaces\ProcessInterface;

class Worker extends Process implements ProcessInterface
{
    // ⚠️ 必须无类型声明！父类 gaia\Process 的 $processConfig 无类型，子类加 array 会 Fatal Error
    protected static $processConfig = [
        'listen'        => '',          // ← 必须填写监听协议，如 'tcp://0.0.0.0:8000'
        'context'       => [],
        'count'         => 1,           // ← 进程数，建议 CPU 核数
        'transport'     => 'tcp',
        'user'          => '',
        'group'         => '',
        'reusePort'     => false,
        'reloadable'    => true,
    ];

    public function onWorkerStart(Worker $worker): void
    {
        // 进程启动初始化业务
    }
}
```

**第 2 步：在 AppCommand 中注册进程**

编辑 `app/command/AppCommand.php`，在 `$process` 属性中添加进程类映射：

```php
class AppCommand extends Command
{
    // ⚠️ 父类 mon\console\Command 的 $default* 属性均无类型，子类加 string 会 Fatal Error
    protected static $defaultName = 'app';
    protected static $defaultDescription = 'Start the App service.';

    // ↓↓↓ 核心：在这里注册所有要启动的进程 ↓↓↓
    protected $process = [
        'worker' => \app\process\Worker::class,
        // 可注册多个进程
        // 'http' => \app\process\HttpProcess::class,
        // 'timer' => \app\process\TimerProcess::class,
    ];

    protected $name = 'app';
    protected $supportPlugin = true;

    public function execute(Input $input, Output $output)
    {
        if (empty($this->process)) {
            return $output->block('未定义启动进程!', 'error');  // ← 空数组会报此错
        }
        if (empty($this->name)) {
            return $output->block('未定义应用名称!', 'error');
        }

        Gaia::instance()->runProcess($this->process);
    }
}
```

**第 3 步：启动应用**

```bash
# Linux 下启动（常驻前台，按 Ctrl+C 停止）
php gaia app

# Windows 下启动（自动生成 runtime/gaia/windows/start_*.php 并管理）
php gaia app
```

### 1.4.5 新增自定义 CLI 指令【推荐】

通过 `make:cmd` 脚手架生成：

```bash
php gaia make:cmd CleanCache
```

生成的 `app/command/CleanCacheCommand.php` 会自动被 Gaia 加载并出现在指令列表中：

```
- App Commands:
  app           Start the App service.
  cleancache    The custom app command [CleanCache]   ← 自动注册
```

**命名规则**：脚手架生成时自动将 `$defaultName` 设为小写（如 `cleancache`），建议手动改为小驼峰或短横线格式（如 `clean:cache`）以符合语义。

### 1.4.6 配置查看

```bash
# 查看所有配置
php gaia config:get

# 查看指定配置节点（支持点号分隔）
php gaia config:get redis          # 等价于 config('redis')
php gaia config:get app.debug      # 等价于 config('app.debug')
```

### 1.4.7 打包部署

```bash
# 打包为 phar 文件（生成 build/gaia.phar + build/gaia.bin）
php gaia build:phar

# 编译为二进制可执行文件
php gaia build:bin
```

### 1.4.8 启动流程架构图

```
php gaia app
    │
    ▼
gaia (CLI 入口脚本)
    │
    ▼
\gaia\App::init()  ─── 加载 .env + config/
    │
    ▼
\support\Plugin::register()  ─── 启动插件 Bootstrap
    │
    ▼
AppCommand::execute()
    │  读取 $this->process 数组
    ▼
Gaia::instance()->runProcess($process)
    │
    ├── OS::isWindows() ?
    │     ├── 是 → 生成 runtime/gaia/windows/start_*.php → proc_open 管理
    │     └── 否 → Worker::runAll() 常驻内存
    │
    ▼
gaia\Process::getProcessConfig()  ─── 每个进程的配置
    │
    ▼
Gaia::bootstrap()  ─── 创建 Worker + 绑定回调
    │
    ▼
Worker::onWorkerStart 钩子
    ├── Env::load() 加载 .env
    ├── Config::loadDir() 加载 config/
    ├── Logger::registerChannel() 初始化日志
    ├── Event::trigger('process_start') 钩子
    └── $handler::onWorkerStart() 业务初始化
```

---

## 2. 命名规范

### 2.1 命名空间【强制】

**规则**：命名空间必须与文件目录严格对应，遵循 PSR-4。Gaia 项目根的 PSR-4 映射为 `"": "./"`。

| 位置 | 命名空间 |
|------|----------|
| `app/command/FooCommand.php` | `app\command` |
| `app/process/BarProcess.php` | `app\process` |
| `app/service/BazService.php` | `app\service` |
| `support/Plugin.php` | `support` |

**反例**（命名空间与目录不匹配）：

```php
// 文件：app/service/RedisService.php
namespace app\controller;  // ❌ 应为 app\service
```

**工具检查**：PHP-CS-Fixer `psr-4` 规则、PHPStan level 1+

---

### 2.2 类名【强制】

**规则**：PascalCase（大驼峰），必须与文件名完全一致。后缀需体现角色：

| 角色 | 后缀 | 正例 | 反例 |
|------|------|------|------|
| CLI 指令 | `Command` | `AppCommand` | `App` / `appCommand` |
| 进程驱动 | 无强制后缀（但推荐 `Process`） | `WorkerProcess` | `workerProcess` |
| 业务服务 | `Service` | `RedisService` | `RedisOperate` / `redisservice` |
| 框架支撑 | 无强制后缀 | `Plugin` | `plugin_util` |

**示例**：

```php
// ✅ 正例：文件 RedisService.php
namespace app\service;
class RedisService { /* ... */ }

// ❌ 反例：文件名 RedisOperate.php，类名 RedisOperate
// 既不符合后缀约定，也丢失 Service 语义
```

**工具检查**：PHP-CS-Fixer `class_name` 规则、PHPStan level 2+

---

### 2.3 方法名【强制】

**规则**：camelCase（小驼峰）。公共 API 方法需体现动作意图。私有/保护方法前可加 `_` 前缀区分。

```php
// ✅ 正例
public function getRedis(array $config = [], bool $reset = false): Redis
public function tryExecCommand(array $config, string $command, ...$options)
protected function keep(string $key, int $ping): int

// ❌ 反例：下划线命名（PHP 4 风格）
public function get_redis() { /* ... */ }
// ❌ 反例：蛇形命名贯穿
public function set_ex($key, $expire, $value) { /* ... */ }
// ❌ 反例：动作模糊
public function doIt() { /* ... */ }
```

**特殊约定**：RedisService 中 Redis 原生命令包装方法（`hSet` / `rPush` / `zRange` 等）保留 Redis 原命令的大小写习惯（小驼峰），如 `hDel`、`lLen`。批量操作方法用 `batch_` 前缀（如 `batch_lpop`、`batch_rPush`）。

**工具检查**：PHP-CS-Fixer `camel_case_methods` 规则、PHPStan level 3+

---

### 2.4 变量与属性名【强制】

**规则**：camelCase。静态属性用 `$xxx_map` 数组映射时例外。

```php
// ✅ 正例
protected $pool = [];
protected $keeps = [];
protected $err_max = 5;      // 特殊：max/min 常量语义可用 snake_case
protected $err_count = 0;     // 同上

// ❌ 反例
protected $RedisPool = [];    // 首字母大写
protected $pool_list = [];    // snake_case 贯穿
protected $_keeps = [];       // 下划线前缀（类属性禁止）
```

**常量命名**（类内 const）【强制】：UPPER_SNAKE_CASE。

```php
// ✅ 正例（LockService 中的实际代码）
const MARK = 'MON_LOCK:';
const HASH_ALL_COUNT_KEY_PREFIX = 'HT_LOCK:';
const HASH_ALL_COUNT_FIELD_PREFIX = 'CNT:';

// ❌ 反例
const mark = 'MON_LOCK:';          // 小写
const Lock_Prefix = 'MON_LOCK:';   // Pascal + snake 混用
```

**工具检查**：PHP-CS-Fixer `constant_case`、`camel_case_variables` 规则

---

### 2.5 配置文件命名【强制】

**规则**：配置文件名必须是 `app.php`、`redis.php`、`log.php` 这类**小写无下划线**形式，与 `Config::instance()->get('redis.host')` 的访问路径对应。

```
config/
├── app.php      → config('app.debug')
├── redis.php    → config('redis.host')
├── log.php      → config('log.default.format.handler')
└── email.php    → config('email.host')
```

**禁止**：`RedisConfig.php`、`log_config.php` 等非小写命名。

**工具检查**：无自动工具（目录约定）

---

### 2.6 数据库表/字段命名【推荐】

> Gaia 当前未引入数据库层，此节标注「待补充」。若引入 MySQL/SQLite，约定如下：

- 表名：`snake_case`，如 `user_order`、`sys_config`
- 字段名：`snake_case`，主键 `id`，时间戳 `created_at` / `updated_at`
- 索引名：`idx_{表名}_{字段名}`

---

## 3. 代码结构规范

### 3.1 分层模型【强制】

Gaia 作为 CLI + Workerman 常驻内存框架，采用以下**三层模型**：

```
┌──────────────────────────────────────┐
│  app/command/    ← 入口层（CLI 指令）  │
│  app/process/    ← 入口层（进程驱动）  │
├──────────────────────────────────────┤
│  app/service/    ← 业务层（Service）   │
├──────────────────────────────────────┤
│  config/         ← 配置层             │
│  support/        ← 框架支撑层         │
└──────────────────────────────────────┘
```

**依赖方向**：上层 → 下层（单向），禁止反向依赖、禁止同层循环依赖。

```
command → service → config
process → service → config
service → config（可依赖 config，不可依赖 command/process）
```

### 3.2 Service 层【强制】

**规则**：所有业务逻辑必须封装在 `app/service/` 下的 Service 类中。Service 类**必须**使用 `mon\util\Instance` trait 实现单例。

```php
// ✅ 正例（RedisService 中的实际代码）
namespace app\service;

use mon\util\Instance;

class RedisService
{
    use Instance;  // ← 强制

    protected function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('redis', []));
    }
}

// 调用方式：
$redis = RedisService::instance()->getRedis();
```

**反例**：

```php
// ❌ 反例 1：不使用单例，每次 new 造成连接泄漏
$redis = new RedisService();
$redis->getRedis();

// ❌ 反例 2：Service 中依赖 Command 或 Process（反向依赖）
namespace app\service;
use app\command\AppCommand;  // ❌ 禁止
```

**工具检查**：PHPStan 自定义规则（检测 Service 类是否 use Instance）

---

### 3.3 Process 层【强制】

**规则**：所有进程驱动类**必须**继承 `gaia\Process`，并实现 `static::$processConfig` 属性。

```php
// ✅ 正例（gaia-framework 中的标准写法）
namespace app\process;

use gaia\Process;

class WorkerProcess extends Process
{
    // ⚠️ 必须无类型声明！父类 gaia\Process 的 $processConfig 无类型，子类加 array 会 Fatal Error
    protected static $processConfig = [
        'listen' => 'tcp://0.0.0.0:8000',
        'count' => 4,
        'reloadable' => true,
        // handler 由 Gaia::bootstrap() 自动绑定
    ];

    public function onMessage($connection, $data): void
    {
        // 业务逻辑
    }
}
```

**禁止**：直接继承 `Workerman\Worker` 或绕过 `gaia\ProcessInterface`。

**工具检查**：PHPStan level 2+（检测类继承关系）、PHP-CS-Fixer `no_non_psr4`

---

### 3.4 Command 层【强制】

**规则**：所有 CLI 指令类**必须**继承 `mon\console\Command`，且类名以 `Command` 结尾。

```php
// ✅ 正例（AppCommand 中的实际代码）
namespace app\command;

use mon\console\Command;

class AppCommand extends Command
{
    // ⚠️ 父类 mon\console\Command 的 $default* 属性均无类型，子类加 string 会 Fatal Error
    protected static $defaultName = 'app';
    protected static $defaultDescription = 'Start the App service.';

    public function execute(Input $input, Output $output)
    {
        Gaia::instance()->runProcess($this->process);
    }
}
```

**禁止**：在 Command 的 `execute()` 中直接写业务逻辑，应委托给 Service 层。

```php
// ❌ 反例：Command 直接写业务
public function execute(Input $input, Output $output)
{
    $redis = new \Redis();
    $redis->connect('127.0.0.1', 6379);
    // 业务逻辑... 应封装到 RedisService
}
```

**工具检查**：PHPStan level 2+（检测类继承关系）

---

### 3.5 依赖注入【推荐】

**规则**：Service 间依赖通过构造函数注入或 `Container::instance()->make()` 解析，禁止在 Service 内部直接 `new` 其他 Service。

```php
// ✅ 正例：LockService 通过 instance() 获取 RedisService
namespace app\service;

class LockService
{
    use Instance;

    public function getRedis(): RedisService
    {
        return RedisService::instance();  // ← 正确：通过单例获取
    }
}

// ❌ 反例：在方法内部 new
public function getRedis(): RedisService
{
    return new RedisService();  // ❌ 绕过单例，连接泄漏
}
```

**工具检查**：PHPStan 自定义规则（检测 Service 类内部的 `new` 语句指向其他 Service 类）

---

### 3.6 Workerman 常驻内存红线【强制】

> 以下规则是 Gaia 区别于 PHP-FPM 项目的**核心差异点**，必须严格遵守。FPM 项目的代码风格检查工具不会覆盖这些红线。

#### 红线 1：禁止全局变量污染

```php
// ❌ 反例：在函数/文件中修改全局变量
$GLOBALS['redis_pool'] = [];

// ❌ 反例：在类外修改预定义常量
define('APP_MODE', 'production');  // 在运行时重新定义
```

**正例**：使用 Service 单例的实例属性管理状态。

#### 红线 2：Timer 必须在进程停止时清理

```php
// ✅ 正例：RedisService::keep() 中用数组保存 Timer ID
protected $keeps = [];

public function start()
{
    $this->keeps[$key] = Timer::add($ping, function () use ($redis) {
        $redis->ping();
    });
}

public function stop()
{
    foreach ($this->keeps as $timerId) {
        Timer::del($timerId);  // ← 进程停止时必须清理
    }
    $this->keeps = [];
}

// ❌ 反例：Timer::add 后未保存 ID，无法清理
Timer::add(30, function () { /* ... */ });  // 泄漏
```

#### 红线 3：Redis/MySQL 长连接必须定期保活或在进程重启时关闭

```php
// ✅ 正例：RedisService 通过 ping 参数启用保活
'ping' => 10,  // 每 10 秒 ping 一次

// ✅ 正例：在 onWorkerStart 中注册 $worker->onWorkerStop 闭包，在关闭钩子中关闭连接
public function onWorkerStart(Worker $worker)
{
    // ...
    $worker->onWorkerStop = function (Worker $worker) {
        foreach ($this->pool as $redis) {
            $redis->close();
        }
        $this->pool = [];
    };
}

// ❌ 反例 1：在 Process 类中定义 onWorkerStop() 方法——框架会在启动时同步立即调用它（非关闭钩子）
// ❌ 反例 2：依赖操作系统超时自动断开（进程常驻可能持续数天）
```

#### 红线 4：禁止在类的静态属性中累积请求状态

```php
// ❌ 反例：静态属性在常驻内存中跨请求残留
class StatService
{
    protected static $count = 0;  // 进程内所有请求共享，无法重置

    public function incr(): void
    {
        self::$count++;  // 错误：多 Worker 进程各自独立计数
    }
}

// ✅ 正例：用 Redis/数据库做持久化计数
class StatService
{
    use Instance;

    public function incr(string $key): int
    {
        return RedisService::instance()->incrBy('stat:' . $key, 1);
    }
}
```

#### 红线 5：`declare(strict_types=1)` 必须在每个 PHP 文件顶部

```php
<?php
declare(strict_types=1);  // ← 每个 .php 文件都必须有

namespace app\service;
```

**反例**：缺少 `declare(strict_types=1)` 导致类型强制模式失效，Workerman 长期运行下类型错误会累积。

**工具检查**：PHP-CS-Fixer `declare_strict_types` 规则

---

### 3.7 文件行数限制【推荐】

| 类型 | 单行最大字符数 | 单文件最大行数 |
|------|---------------|---------------|
| PHP 文件 | 120 | 500 行（RedisService 例外，已超出，建议拆分） |
| 配置文件 | 无限制 | 200 行 |

**RedisService 现状**：1767 行，远超限制。建议后续按 Redis 数据类型拆分为 `RedisStringTrait` / `RedisHashTrait` / `RedisListTrait` 等。标注「待补充」。

**工具检查**：PHP-CS-Fixer `line_length`、SonarQube 文件行数规则

---

## 4. 注释与文档规范

### 4.1 文件头注释【推荐】

Gaia 使用 `composer.json` 作为项目级元信息，PHP 文件头部**不强制**加版权头。若加则统一格式：

```php
<?php
declare(strict_types=1);
/*
|--------------------------------------------------------------------------
| 文件级注释（可选）
|--------------------------------------------------------------------------
*/
```

**禁止**：在 `declare(strict_types=1)` 和 `namespace` 之间插入版权头。

---

### 4.2 类注释【强制】

**规则**：每个类**必须**有简短用途说明。格式参考 Gaia 现有风格：

```php
/**
 * Redis操作类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.2.2 2023-05-12 增加连接保活功能
 */
class RedisService
{
    // ...
}
```

**必填项**：
- 第一行：类的一句话用途
- `@author`：作者姓名 + 邮箱
- `@version`：版本号 + 修改日期 + 变更说明

**可选项**：`@copyright`、`@see`、`@link`

**禁止**：注释与类用途不符或缺失。

**工具检查**：PHPStan level 1+（`@author`、`@version` 非强制但可通过自定义规则检查）

---

### 4.3 方法注释【强制】

**规则**：所有公共方法**必须**有注释。私有/保护方法推荐添加。格式：

```php
/**
 * 获取Redis实例
 *
 * @param array $config     配置信息
 * @param boolean $reset    是否重置连接
 * @return Redis
 * @throws BadFunctionCallException
 */
public function getRedis(array $config = [], bool $reset = false): Redis
{
    // ...
}
```

**必填项**：
- 方法用途（第一行）
- `@param`：参数名 + 类型 + 说明（有参数时）
- `@return`：返回类型 + 说明（有返回值时）
- `@throws`：可能抛出的异常（可能抛异常时）

**禁止**：注释与签名不一致（如注释写 `@param array $config` 但实际类型是 `?array`）。

**工具检查**：PHPStan `missing_throws_tag`、Infection（注释一致性测试）

---

### 4.4 属性注释【强制】

**规则**：所有类属性**必须**有 `@var` 注释。

```php
/**
 * Redis连接池
 *
 * @var array
 */
protected $pool = [];

/**
 * 配置信息
 *
 * @var array{host: string, port: int, auth: string, prefix: string, database: int, timeout: int, ping: int, persistent: bool}
 */
protected $config = [
    'host' => '127.0.0.1',
    // ...
];
```

**PHP 8.4 改进**：有原生类型声明的属性可用简化注释：

```php
/** @var array<string, mixed> */
protected array $config = [];

/** @var bool 是否启用保活 */
protected bool $pingEnabled = false;
```

**工具检查**：PHPStan level 2+（`@var` 类型不匹配报错）

---

### 4.5 TODO / FIXME 注释【推荐】

**规则**：临时标记用统一格式，标注负责人和日期，便于追踪：

```php
// TODO(@username): 2026-10-19 后续拆分 RedisService 按数据类型
// FIXME: 当 Redis 连接池配置 key 发生冲突时未处理
// WORKAROUND: 临时方案，待 mon\util\Instance 升级后移除
```

**禁止**：无负责人无日期的 TODO（如 `// TODO fix this`）。

**工具检查**：无自动工具（SonarQube 可检测未分配 TODO）

---

### 4.6 `declare(strict_types=1)` 位置【强制】

**规则**：`declare(strict_types=1);` 必须在 `<?php` 之后、任何代码之前，且独占一行。

```php
<?php
declare(strict_types=1);  // ← 第 2 行，独占

namespace app\service;     // ← 第 4 行（空行分隔）
```

**禁止**：
- 与 `<?php` 同行：`<?php declare(strict_types=1);`
- 与 `namespace` 同行
- 中间插入文件头注释

**工具检查**：PHP-CS-Fixer `declare_strict_types` 规则（自动修复）

---

## 5. 异常处理与日志规范

### 5.1 异常基类【强制】

**规则**：使用 PHP 内置异常类即可，不强制自定义 GaiaException 基类。但需按异常性质选择合适的内置类：

| 场景 | 应使用的内置异常类 |
|------|-------------------|
| 功能/扩展未安装 | `BadFunctionCallException` |
| 参数类型/值无效 | `InvalidArgumentException` |
| 运行时条件不满足（如锁已存在、Redis 连接断开） | `RuntimeException` |
| 配置文件读取失败 | `RuntimeException` |
| 文件/目录不存在 | `RuntimeException`（或 `FileNotFoundException`，PHP 8.1+） |

**正例**（来自 RedisService 和 LockService 的实际代码）：

```php
// RedisService
if (!extension_loaded('redis')) {
    throw new BadFunctionCallException('Not support Redis');
}

// LockService
if ($this->getRedis()->exists($lock_key)) {
    throw new RuntimeException('加锁失败, 锁已经存在 => ' . $lock_key);
}

// LockService::unLock
if (empty($lock_key) || empty($app_id)) {
    throw new RuntimeException('解锁参数错误');
}
```

**反例**：

```php
// ❌ 反例：用 Exception 作为万能异常
throw new Exception('error');  // 无法区分错误类型，catch 只能写 catch (\Exception $e)

// ❌ 反例：异常消息含糊
throw new RuntimeException('wrong');  // 无上下文，日志无法定位
```

**异常消息**【强制】：必须包含足够的调试上下文（键名、文件名、配置值等），便于日志排查。

**工具检查**：PHPStan `too_many_thrown_exceptions`、Psalm `ReservedWord`

---

### 5.2 try-catch 范围【强制】

**规则**：catch 块必须精确捕获，禁止 `catch (\Throwable $e)` 吞掉异常。Workerman 常驻内存中**严禁**在业务逻辑中静默吞异常。

```php
// ✅ 正例：RedisService::tryExecCommand 中精确捕获并重试
public function tryExecCommand(array $config, string $command, ...$options)
{
    try {
        $redis = $this->getRedis($config);
        $result = $redis->{$command}(...$options);
        $this->err_count = 0;
        return $result;
    } catch (Throwable $e) {
        $msg = strtolower($e->getMessage());
        if (($this->err_max >= $this->err_count) && ($msg === 'connection lost' || strpos($msg, 'went away'))) {
            $this->err_count++;
            $this->getRedis($config, true);
            return $this->tryExecCommand($config, $command, ...$options);  // 重试
        }
        throw $e;  // ← 非连接异常必须重新抛出
    }
}

// ❌ 反例 1：catch 后不 throw
try {
    $this->doSomething();
} catch (Throwable $e) {
    // 什么都不做 → 异常被吞，进程状态已损坏但无日志
}

// ❌ 反例 2：catch 后仅日志不重新抛出
try {
    $this->doSomething();
} catch (Throwable $e) {
    Logger::error('doSomething failed');  // 日志了但没 throw → 进程继续运行但状态可能不一致
}
```

**Workerman 进程级异常处理**【强制】：进程的 `onMessage` / `onConnect` 等回调中**外层必须**有 try-catch，防止单个请求异常导致整个 Worker 进程退出：

```php
// ✅ 正例
class WorkerProcess extends Process
{
    public function onMessage($connection, $data): void
    {
        try {
            // 业务逻辑
            $result = RedisService::instance()->get('key');
            $connection->send($result);
        } catch (Throwable $e) {
            Logger::channel()->error('onMessage failed', ['data' => $data, 'error' => $e->getMessage()]);
            $connection->close();  // 关闭连接而非让异常冒泡到 Worker
        }
    }
}
```

**工具检查**：PHPStan `catchableThrowable`（检测不应捕获的 Throwable 类型）

---

### 5.3 日志使用规范【强制】

**规则**：统一使用 `mon\log\Logger` 或其静态代理，禁止直接 `file_put_contents` 写日志。

```php
// ✅ 正例 1：通过通道实例调用
use mon\log\Logger;

Logger::instance()->channel('default')->info('Redis连接成功', ['host' => '127.0.0.1']);
Logger::instance()->channel('default')->error('加锁失败', ['lock_key' => $lock_key]);

// ✅ 正例 2：静态代理（__callStatic 实现）
Logger::info('Redis连接成功', ['host' => '127.0.0.1']);
Logger::error('加锁失败', ['lock_key' => $lock_key]);
Logger::debug('tryExecCommand', ['config' => $config, 'command' => $command]);

// ❌ 反例 1：直接写文件
file_put_contents(RUNTIME_PATH . '/log/error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);

// ❌ 反例 2：用 echo 代替日志（常驻内存进程中 echo 输出被 Workerman 重定向到 stdout.log）
echo 'error: ' . $e->getMessage();  // 应使用 Logger
```

**日志级别使用**【强制】：

| 级别 | 使用场景 |
|------|----------|
| `Logger::emergency` | 系统不可用（Redis 全部断连、磁盘满） |
| `Logger::alert` | 需要立即修复的状态（进程数异常） |
| `Logger::critical` | 严重但非致命错误 |
| `Logger::error` | 业务操作失败（加锁失败、连接断开重试次数耗尽） |
| `Logger::warning` | 非预期但可继续运行的状态（连接已存在、配置使用默认值） |
| `Logger::notice` | 正常但值得记录的事件（进程启动、配置变更） |
| `Logger::info` | 关键业务流程节点 |
| `Logger::debug` | 调试信息（生产环境关闭 trace） |

**配置文件**（来自 `config/log.php` 实际代码）：

```php
return [
    'default' => [
        'format' => [
            'handler' => LineFormat::class,
            'config'  => [
                'level'       => true,
                'date'        => true,
                'date_format' => 'Y-m-d H:i:s',
                'trace'       => false,     // ← 生产关闭 trace
                'layer'       => 3,
            ],
        ],
        'record' => [
            'handler' => FileRecord::class,
            'config'  => [
                'save'    => true,
                'clear'   => true,
                'logName' => '',
                'maxSize' => 20480000,       // 20MB
                'logPath' => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'log',
                'rollNum' => 3,
            ],
        ],
    ],
];
```

**工具检查**：无自动工具（日志调用统一通过 Logger::class）

---

### 5.4 资源清理规范【强制】

> ⚠️ **框架陷阱**：gaia-framework 1.2.x 中，Process 类的 `onWorkerStop()` 方法会在进程**启动时**被框架同步立即调用（`Gaia::bindWorker()` 在执行完 `onWorkerStart` 后直接调用它），**它不是进程关闭钩子**。
> 禁止在 Process 类中定义 `onWorkerStop()` 方法承载清理逻辑。正确做法：在 `onWorkerStart()` 中显式注册 `$worker->onWorkerStop` 闭包（框架不会覆盖它，Workerman 进程退出时真正调用）。

进程退出前，以下资源**必须**在 `$worker->onWorkerStop` 闭包中主动释放：

| 资源类型 | 清理方式 |
|----------|----------|
| Redis 长连接 | `$redis->close()` |
| MySQL 长连接 | `$pdo = null;` 或 `$pdo->close()` |
| Timer 定时器 | `Timer::del($timerId)` |
| 文件句柄 | `fclose($handle)` |
| 共享内存 | `shmop_delete($shmid); shmop_close($shmid);` |

```php
// ✅ 正例：在 onWorkerStart 中注册关闭钩子，统一清理资源
public function onWorkerStart(Worker $worker): void
{
    // 业务初始化...
    $this->keeps['heartbeat'] = Timer::add(10, function () { /* ... */ });

    // 注册进程关闭钩子
    $worker->onWorkerStop = function (Worker $worker): void {
        // 清理 Timer
        foreach ($this->keeps as $timerId) {
            Timer::del($timerId);
        }
        $this->keeps = [];

        // 关闭 Redis 长连接
        foreach ($this->pool as $redis) {
            $redis->close();
        }
        $this->pool = [];
    };
}

// ❌ 反例 1：在 Process 类中定义 onWorkerStop() 方法——框架启动时会立即调用它，Timer 刚注册就被删除
// ❌ 反例 2：依赖 Worker 进程退出时操作系统自动释放
// 常驻内存可能运行数天，连接池会持续增长
```

**工具检查**：无自动工具（Code Review 强制检查）

---

## 6. 代码提交规范

> 标注「待团队确认」，以下为推荐默认值。

### 6.1 Commit Message【推荐】

采用 **Conventional Commits** 格式：

```
<type>(<scope>): <subject>

<body>

<footer>
```

**type 可选值**：

| type | 说明 |
|------|------|
| `feat` | 新功能（如新增 Service/Process/Command） |
| `fix` | 修复 Bug（如 Redis 连接泄漏、锁逻辑错误） |
| `refactor` | 重构（不改变行为） |
| `perf` | 性能优化 |
| `docs` | 仅修改文档/注释 |
| `style` | 代码风格（空格/换行/分号，不影响逻辑） |
| `test` | 测试相关 |
| `chore` | 构建/工具/依赖更新 |

**scope**：影响范围，如 `service`、`process`、`command`、`config`、`gaia-framework`。

**正例**：

```
feat(service): 新增 LockService 分布式锁服务

- 基于 Redis 实现加锁/解锁
- 支持 Lua 脚本保证原子性
- 实现"解铃还需系铃人"机制

Closes #15
```

```
fix(redis): 修复 RedisService 连接池 key 计算错误

serialize 输出受 PHP 版本影响，改用 json_encode 保证稳定性
```

**反例**：

```
update              // ❌ 无类型无范围
fix bug             // ❌ 无 scope，subject 模糊
redis modify        // ❌ 不是 Conventional Commits 格式
```

---

### 6.2 分支模型【推荐】

> 标注「待团队确认」。Gaia 作为单仓库框架骨架，推荐 GitHub Flow：

```
main ────────────────────────────────► （生产分支，可随时部署）
  └── feature/redis-pool    ──── PR ──►
  └── fix/lock-expire       ──── PR ──►
  └── docs/code-style       ──── PR ──►
```

**分支命名规则**：`{type}/{简短描述}`，全部小写，连字符分隔。

**禁止**：直接向 `main` 分支推送，必须走 PR。

---

### 6.3 Code Review 检查项【强制】

见下一节 **Code Review Checklist**。

---

## 7. Code Review Checklist

> 本清单与前面的强制规则一一对应，可直接作为 PR Review 的勾选模板。

### 7.1 基础规范（必过）

- [ ] 每个 PHP 文件顶部有 `declare(strict_types=1);`
- [ ] 文件以 `<?php` 开头，无 BOM，无多余空行
- [ ] 命名空间与文件目录严格对应（PSR-4）
- [ ] 类名 PascalCase，方法/变量 camelCase，常量 UPPER_SNAKE_CASE
- [ ] 每行 ≤ 120 字符
- [ ] 无尾随空格、无多余空行、换行符为 LF
- [ ] 所有公共方法有注释（`@param` / `@return` / `@throws` 与签名一致）
- [ ] 所有类属性有 `@var` 注释

### 7.2 分层与依赖

- [ ] Service 类在 `app/service/` 下，且使用 `use Instance` 单例
- [ ] Service 未反向依赖 Command 或 Process
- [ ] Process 类在 `app/process/` 下，继承 `gaia\Process`，有无类型的 `static::$processConfig`（加类型会 Fatal Error）
- [ ] Process 类的 `$processConfig['listen']` 不为空（脚手架默认空字符串会导致无法绑定端口）
- [ ] Process 类未定义 `onWorkerStop()` 方法（框架启动时会同步调用它）
- [ ] 新增 Process 类已在 `AppCommand::$process` 数组中注册（否则 `php gaia app` 不会启动它）
- [ ] Command 类的 `$defaultName` / `$defaultDescription` / `$defaultGroup` 为无类型声明（父类均无类型）
- [ ] 新增 Command 类已被 Gaia 自动加载（`php gaia` 指令列表中可见）
- [ ] Command 类在 `app/command/` 下，继承 `mon\console\Command`，类名以 `Command` 结尾
- [ ] Command 未直接写业务逻辑，委托给 Service
- [ ] 无全局变量修改

### 7.3 Workerman 常驻内存红线

- [ ] Timer 有保存 ID；重复注册前先 `Timer::del` 旧 ID
- [ ] 关闭清理通过 `onWorkerStart` 中注册的 `$worker->onWorkerStop` 闭包实现，未定义 `onWorkerStop()` 方法
- [ ] Redis/MySQL 长连接有保活或在关闭钩子中关闭
- [ ] 无静态属性累积请求状态（如 `self::$count++`）
- [ ] Process 的回调方法（`onMessage` / `onConnect` 等）外层有 try-catch
- [ ] 无单例属性在多次调用间残留状态（如 `$this->err_count` 需在连接正常时清零）

### 7.4 异常与日志

- [ ] 使用 PHP 内置异常类（`RuntimeException` / `InvalidArgumentException` / `BadFunctionCallException`）
- [ ] 异常消息包含足够上下文（键名、配置值等）
- [ ] catch 块精确捕获，非预期异常必须 `throw $e`
- [ ] 无静默吞异常（catch 后既不日志也不 throw）
- [ ] 日志统一用 `Logger::info/error/warning`，无 `file_put_contents` 或 `echo` 写日志
- [ ] 日志级别正确使用（error 级用于业务失败，debug 级生产关闭）

### 7.5 安全性

- [ ] Redis/MySQL 连接密码未硬编码，使用 `config()` 或 `env()` 读取
- [ ] 配置文件 `config/*.php` 中无明文密码（通过 `.env` 读取）
- [ ] 未在 `runtime/` 或 `config/` 下留下调试文件
- [ ] `.gitignore` 正确排除 `.env`、`runtime/`、`vendor/`

### 7.6 提交规范

- [ ] Commit Message 符合 Conventional Commits 格式
- [ ] 分支命名符合 `{type}/{desc}` 规则
- [ ] 无合并垃圾 Commit（如 `WIP` / `update` / `fix bug`）
- [ ] 本次变更范围合理（单 Commit ≤ 500 行变更，超过则拆分）

### 7.7 工具检查（可选但推荐）

- [ ] PHP-CS-Fixer 通过（或手动运行 `php-cs-fixer fix` 后提交）
- [ ] PHPStan level 1+ 通过
- [ ] 无 PHP Warning/Notice（`error_reporting(E_ALL)` 下测试）

---

## 附录：工具配置建议

### PHP-CS-Fixer（PSR-12 + 自定义规则）

```json
// .php-cs-fixer.php 配置建议
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'line_length' => ['limit' => 120],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder(PhpCsFixer\Finder::create()
        ->in(__DIR__ . '/app')
        ->in(__DIR__ . '/support')
        ->in(__DIR__ . '/config')
        ->exclude('vendor'));
```

### PHPStan

```neon
# phpstan.neon
parameters:
    level: 1
    paths:
        - app
        - support
    excludePaths:
        - vendor/*
```

---

> **文档维护说明**：本规范随 Gaia 版本迭代更新。新增或修改规则时，必须同步更新 Code Review Checklist 和 Skill 配置。标注「待补充」的章节在信息明确后补全。
