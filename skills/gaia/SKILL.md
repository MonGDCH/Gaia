---
name: gaia
description: 为 Gaia Workerman 框架开发 PHP CLI 常驻内存服务 当用户要求创建进程 服务 指令 注册业务逻辑 或调试 Workerman 常驻内存问题时使用
---

# Gaia 框架开发技能

你正在为 Gaia 项目开发代码——这是一款基于 Workerman 的 PHP 8.4 CLI 常驻内存多进程服务框架。**所有生成的代码必须严格遵循下方约定。**

---

## 🔔 触发关键词

当用户输入包含以下任意关键词时，**必须触发本技能**：

| 关键词 | 典型请求 |
|--------|----------|
| `Gaia` / `gaia` | "帮我用 Gaia 写个 Redis 队列消费进程" |
| `Workerman 进程` / `常驻内存` | "Workerman 常驻内存怎么写定时器？" |
| `php gaia` | "php gaia app 报未定义启动进程怎么修？" |
| `make:process` / `make:cmd` | "帮我生成一个 make:process" |
| `gaia\Process` / `gaia Process` | "gaia\Process 怎么注册？" |
| `Timer 泄漏` / `定时器泄漏` | "Workerman Timer add 后要 del 吗？" |
| `多进程` / `进程管理` | "Gaia 怎么配置多进程？" |
| `Worker` / `WorkerStart` / `WorkerStop` | "onWorkerStop 要清理什么？" |
| `进程驱动` / `进程配置` | "生成的进程 listen 填什么？" |
| `AppCommand` | "AppCommand 的 process 数组怎么填？" |
| `Service 单例` / `use Instance` | "Gaia 的 Service 必须用单例吗？" |
| `锁服务` / `Redis 连接池` | "帮我写个 Gaia 的 LockService" |
| `phar 打包` / `build:phar` | "Gaia 怎么打包成 phar？" |
| `Gaia 指令` / `CLI 指令` | "帮我加个清理缓存的 CLI 指令" |

### 触发示例

**用户说**："帮我写一个 Gaia 进程，监听 9000 端口消费 Redis 队列"
→ **触发**："Gaia" + "进程" → 生成 Process 代码 + 注册到 AppCommand

**用户说**："新增一个 Redis 锁的 Service"
→ **触发**："Gaia Service" → 生成 Service 骨架 + 单例模式

**用户说**："php gaia app 报未定义启动进程"
→ **触发**："php gaia" → 检查 AppCommand::$process 是否为空

**用户说**："Workerman 的 Timer 要清理吗？"
→ **触发**："Workerman" + "Timer" → 告知红线规则 + 代码示例

---

## 🚀 快速命令速查

| 操作 | 命令 / 步骤 |
|------|-------------|
| 查看所有 CLI 指令 | `php gaia` |
| 生成进程驱动类 | `php gaia make:process 进程名` → 编辑 `$processConfig['listen']` → 注册到 `AppCommand::$process` |
| 生成 CLI 指令 | `php gaia make:cmd 指令名` → 自动注册 |
| 生成 Service 服务 | 手动创建 `app/service/名称Service.php`，使用 `use Instance` 单例 |
| 启动应用 | `php gaia app` |
| 查看配置 | `php gaia config:get [key.path]` |
| 打包 phar | `php gaia build:phar` |
| 代码规范检查 | `php .agents/skills/gaia/scripts/lint.php` |

---

## 🔴 强制规则（不可违反）

### 规则 1：文件头格式【强制】

每个 `.php` 文件**必须**以以下格式开头，**禁止**在 `<?php` 和 `declare` 之间插入版权头：

```php
<?php
declare(strict_types=1);

namespace app\service;

// use ...
```

**反例**：

```php
<?php
/* 版权头 */          // ❌ 禁止
declare(strict_types=1);

<?php declare(strict_types=1);  // ❌ 禁止与 <?php 同行
```

### 规则 2：命名空间映射（PSR-4，根为 ""）【强制】

composer.json 配置 `"": "./"`，文件路径直接对应完全限定名：

| 文件路径 | 完全限定名 |
|----------|-----------|
| `app/service/RedisService.php` | `app\service\RedisService` |
| `app/process/Worker.php` | `app\process\Worker` |
| `app/command/CleanCacheCommand.php` | `app\command\CleanCacheCommand` |
| `support/Plugin.php` | `support\Plugin` |

**反例**：`app/service/RedisService.php` 里写 `namespace app\controller;` ❌

### 规则 3：分层依赖方向（单向）【强制】

```
command → service → config
process → service → config
service → config（❌ 禁止依赖 command 或 process）
```

Service 类**必须**使用 `use mon\util\Instance;` 单例 trait。

**反例**：

```php
// ❌ Service 反向依赖 Command
namespace app\service;
use app\command\AppCommand;
```

### 规则 4：类角色后缀【强制】

| 角色 | 必需后缀 | 必须继承 |
|------|---------|---------|
| Service | `Service` | 无（但必须 use Instance trait） |
| Process | 推荐 `Process` | `gaia\Process` |
| Command | `Command` | `mon\console\Command` |

**反例**：

```php
class RedisOperate { }        // ❌ 没有 Service 后缀
class Foo extends Command { } // ❌ 没有 Command 后缀
```

### 规则 5：Workerman 常驻内存 6 条红线【强制】

| 红线 | 说明 | 违反后果 |
|------|------|---------|
| ① Timer 必须清理 | `Timer::add` 返回的 ID **必须**保存；同一业务重复注册前必须先 `Timer::del` 旧 ID；关闭清理通过 `$worker->onWorkerStop` 闭包注册（见规则 9） | Timer 泄漏，进程内存持续增长 |
| ② 长连接必须清理 | Redis/MySQL 长连接必须有保活（ping）或在关闭钩子中 close；`persistent => false` 的短连接随进程销毁，无需处理 | 连接泄漏，连接池耗尽 |
| ③ 禁止静态属性存请求状态 | `self::$count++` 这类写法在常驻内存中跨请求残留 | 数据错乱，多 Worker 状态不一致 |
| ④ 回调必须 try-catch | `onMessage` / `onConnect` 等回调方法外层**必须**包 `try-catch(\Throwable $e)` | 单个请求异常导致整个 Worker 退出 |
| ⑤ 禁止修改超全局变量 | 禁止 `$GLOBALS[...]`、禁止运行时 `define()` 覆盖已有常量 | 进程内全局污染，难以排查 |
| ⑥ 禁止定义 onWorkerStop 方法 | 框架会在进程**启动时**同步调用同名方法（详见规则 9），清理逻辑必须走 `$worker->onWorkerStop` 闭包 | Timer 刚注册即被删除，进程静默失效 |

> ⚠️ **属性声明陷阱**：`gaia\Process::$processConfig`、`mon\console\Command::$defaultName / $defaultDescription / $defaultGroup` 在父类中均为**无类型**声明。子类必须同样无类型声明（`protected static $processConfig = [...]`），**加 `array` / `string` 类型会直接 Fatal Error**（`Type of ... must not be defined (as in class ...)`）。

**红线 ① 正确示例**：

```php
class WorkerProcess extends Process
{
    // ✅ 必须无类型！父类 gaia\Process 声明的是无类型属性，加 array 会 Fatal Error
    protected static $processConfig = [
        'listen' => 'tcp://0.0.0.0:8000',
        'count'  => 4,
    ];

    /** @var array<string, int> Timer ID 映射 */
    protected array $timerIds = [];

    public function onWorkerStart(Worker $worker): void
    {
        // ✅ Timer::add 返回值保存
        $this->timerIds['heartbeat'] = Timer::add(30, function () {
            RedisService::instance()->ping();
        });

        // ✅ 关闭钩子必须这样注册（禁止定义 onWorkerStop 方法，见规则 9）
        $worker->onWorkerStop = function (Worker $worker) {
            foreach ($this->timerIds as $timerId) {
                Timer::del($timerId);
            }
            $this->timerIds = [];
        };
    }
}
```

**红线 ④ 正确示例**：

```php
public function onMessage($connection, $data): void
{
    try {
        // ✅ 业务逻辑包裹
        $result = RedisService::instance()->get('key');
        $connection->send($result);
    } catch (\Throwable $e) {
        Logger::error('onMessage 处理失败', ['data' => $data, 'error' => $e->getMessage()]);
        $connection->close();
    }
}
```

**红线 ③ 反例**：

```php
// ❌ 禁止！静态属性在常驻内存中跨请求残留
class StatService
{
    protected static int $count = 0;

    public function incr(): void
    {
        self::$count++;  // 进程内所有请求共享，无法重置
    }
}

// ✅ 正确：用 Redis 做持久化计数
class StatService
{
    use Instance;

    public function incr(string $key): int
    {
        return RedisService::instance()->incrBy('stat:' . $key, 1);
    }
}
```

### 规则 6：Process 必须注册【强制】

通过 `make:process` 生成的进程类，**必须**在 `app/command/AppCommand.php` 的 `$process` 数组中注册：

```php
class AppCommand extends Command
{
    // ↓↓↓ 在这里注册所有要启动的进程 ↓↓↓（与实际 AppCommand 一致，无类型声明）
    protected $process = [
        'worker'    => \app\process\Worker::class,
        'http'      => \app\process\HttpProcess::class,
        'cron'      => \app\process\CronProcess::class,
    ];
}
```

**不注册的后果**：`php gaia app` 报「未定义启动进程!」然后退出。

### 规则 7：异常处理【强制】

- 使用 PHP 内置异常类：`RuntimeException` / `InvalidArgumentException` / `BadFunctionCallException`
- catch 块**必须**重新抛出非预期异常：`catch (Throwable $e) { ...; throw $e; }`
- **禁止**静默吞异常
- 异常消息**必须**包含上下文（键名、配置值等）

```php
// ✅ 正例
public function lock(string $uid, string $logic_id): array
{
    $lock_key = 'LOCK:' . $uid . ':' . $logic_id;
    if ($this->getRedis()->exists($lock_key)) {
        throw new RuntimeException('加锁失败, 锁已经存在 => ' . $lock_key);
    }
    // ...
}

// ❌ 反例 1：吞异常
try { $this->lock(); } catch (Throwable $e) { /* 什么都不做 */ }

// ❌ 反例 2：只日志不 throw
try { $this->lock(); } catch (Throwable $e) { Logger::error('加锁失败'); }

// ❌ 反例 3：异常消息无上下文
throw new RuntimeException('error');
```

### 规则 8：日志使用【强制】

统一使用 `mon\log\Logger`，**禁止**用 `echo` / `file_put_contents` / `error_log` 写业务日志。

```php
// ✅ 正例
use mon\log\Logger;

Logger::info('Redis连接成功', ['host' => '127.0.0.1', 'port' => 6379]);
Logger::error('加锁失败', ['lock_key' => $lock_key]);
Logger::warning('连接已存在', ['key' => $key]);
Logger::debug('tryExecCommand', ['config' => $config]);

// ❌ 反例
file_put_contents(RUNTIME_PATH . '/log/error.log', $e->getMessage(), FILE_APPEND);
echo 'error: ' . $e->getMessage();
```

**日志级别使用**：

| 级别 | 场景 |
|------|------|
| `emergency` | 系统不可用（Redis 全部断连、磁盘满） |
| `alert` | 需要立即修复（进程数异常） |
| `critical` | 严重但非致命 |
| `error` | 业务操作失败（加锁失败、重试耗尽） |
| `warning` | 非预期但可继续（连接已存在、用了默认配置） |
| `notice` | 正常但值得记录（进程启动、配置变更） |
| `info` | 关键业务节点 |
| `debug` | 调试信息（生产关闭 trace） |

### 规则 9：进程关闭钩子与资源清理【强制】

> ⚠️ **框架陷阱（重要）**：gaia-framework 1.2.x 中，Process 类的 `onWorkerStop()` 方法会在进程**启动时**被框架同步立即调用（`Gaia::bindWorker()` 在执行完 `onWorkerStart` 后直接调用它），**它不是进程关闭钩子**。
> **禁止**在 Process 类中定义 `onWorkerStop()` 方法承载清理逻辑——否则 Timer 刚注册就被删除，进程静默失效且无任何报错。
> **正确做法**：在 `onWorkerStart()` 中显式注册 `$worker->onWorkerStop` 闭包。框架不会覆盖它，Workerman 进程退出时会真正调用。

关闭钩子中需要清理的资源：

| 资源 | 清理方式（写在 `$worker->onWorkerStop` 闭包内） |
|------|---------|
| Timer | `Timer::del($timerId)` |
| Redis 长连接 | `RedisService::instance()->close()` |
| MySQL PDO | `$pdo = null` |
| 文件句柄 | `fclose($handle)` |

**完整示例**（一个规范的 Process 类）：

```php
<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use Workerman\Timer;
use gaia\interfaces\ProcessInterface;
use app\service\RedisService;
use mon\log\Logger;

/**
 * Redis 队列消费进程
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 2026-10-19
 */
class QueueConsumerProcess extends Process implements ProcessInterface
{
    /**
     * 进程配置（必须无类型声明，与父类 gaia\Process 保持一致）
     *
     * @var array
     */
    protected static $processConfig = [
        'listen'     => 'tcp://0.0.0.0:0',  // 内部进程，无需监听端口
        'context'    => [],
        'count'      => 4,                   // 4 个 Worker 进程并行消费
        'transport'  => 'tcp',
        'user'       => '',
        'group'      => '',
        'reusePort'  => false,
        'reloadable' => true,
    ];

    /**
     * Timer ID 映射
     *
     * @var array<string, integer>
     */
    protected array $timerIds = [];

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        Logger::info('QueueConsumer 进程启动', ['worker_id' => $worker->id]);

        // 注册消费定时器
        $this->timerIds['consume'] = Timer::add(1, function () {
            $this->consume();
        });

        // ✅ 注册真正的进程关闭钩子（框架不会覆盖；Workerman 进程退出时调用）
        $worker->onWorkerStop = function (Worker $worker) {
            Logger::info('QueueConsumer 进程停止', ['worker_id' => $worker->id]);

            // 清理 Timer
            foreach ($this->timerIds as $timerId) {
                Timer::del($timerId);
            }
            $this->timerIds = [];

            // 关闭 Redis 长连接（短连接无需处理）
            try {
                RedisService::instance()->close();
            } catch (\Throwable $e) {
                Logger::warning('Redis 关闭异常', ['error' => $e->getMessage()]);
            }
        };
    }

    /**
     * 消费任务
     *
     * @return void
     */
    protected function consume(): void
    {
        try {
            $task = RedisService::instance()->lPop('task:queue');
            if ($task) {
                Logger::info('任务处理完成', ['task' => $task]);
            }
        } catch (\Throwable $e) {
            Logger::error('消费失败', ['error' => $e->getMessage()]);
        }
    }
}
```

### 规则 10：命名总结【强制】

| 类型 | 格式 | 正例 | 反例 |
|------|------|------|------|
| 类名 | PascalCase | `RedisService` `AppCommand` | `redisservice` `App` |
| 方法/属性 | camelCase | `getRedis` `$err_count` | `get_redis` `$RedisPool` |
| 类常量 | UPPER_SNAKE_CASE | `MARK` `HASH_PREFIX` | `mark` `Lock_Prefix` |
| 配置文件 | 小写无下划线 | `redis.php` `app.php` | `RedisConfig.php` `log_config.php` |
| Command `$defaultName` | 冒号分隔或短横线 | `clean:cache` `queue-consume` | `cleancache` |

---

## 🧱 标准骨架代码

### Service 骨架

```php
<?php
declare(strict_types=1);

namespace app\service;

use mon\util\Instance;
use mon\env\Config;

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
     * 构造方法（protected，禁止外部 new）
     */
    protected function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('{{CONFIG_KEY}}', []));
    }

    /**
     * 示例业务方法
     *
     * @param string $param
     * @return array
     * @throws \RuntimeException
     */
    public function doSomething(string $param): array
    {
        // 业务逻辑...
        return ['param' => $param];
    }
}
```

**调用方式**：`{{NAME}}Service::instance()->doSomething('test');`

### Process 骨架

```php
<?php
declare(strict_types=1);

namespace app\process;

use gaia\Process;
use Workerman\Worker;
use Workerman\Timer;
use gaia\interfaces\ProcessInterface;
use mon\log\Logger;

/**
 * {{NAME}} 进程
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Process extends Process implements ProcessInterface
{
    /**
     * 进程配置（必须无类型声明，与父类 gaia\Process 保持一致，加 array 会 Fatal Error）
     *
     * @var array
     */
    protected static $processConfig = [
        'listen'     => '{{LISTEN}}',       // 必填！如 'tcp://0.0.0.0:8000' 或 'tcp://0.0.0.0:0'（内部进程）
        'context'    => [],
        'count'      => 1,                  // 进程数，建议 CPU 核数
        'transport'  => 'tcp',
        'user'       => '',
        'group'      => '',
        'reusePort'  => false,
        'reloadable' => true,
    ];

    /**
     * Timer ID 映射
     *
     * @var array<string, integer>
     */
    protected array $timerIds = [];

    public function onWorkerStart(Worker $worker): void
    {
        Logger::info('{{NAME}} 进程启动', ['worker_id' => $worker->id]);

        // TODO: 注册 Timer 等初始化
        // $this->timerIds['xxx'] = Timer::add(1, function () { ... });

        // ✅ 进程关闭钩子必须在此注册（禁止定义 onWorkerStop 方法，框架启动时会立即调用同名方法）
        $worker->onWorkerStop = function (Worker $worker) {
            Logger::info('{{NAME}} 进程停止', ['worker_id' => $worker->id]);
            // TODO: 清理 Timer、关闭长连接
            foreach ($this->timerIds as $timerId) {
                Timer::del($timerId);
            }
            $this->timerIds = [];
        };
    }
}
```

### Command 骨架

```php
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

    public function execute(Input $input, Output $output): int
    {
        // ✅ 委托给 Service，不要直接写业务逻辑
        return $output->write('Done.');
    }
}
```

### Config 骨架

```php
<?php
declare(strict_types=1);

return [
    // {{配置描述}}
    'default' => [
        '{{KEY}}' => '{{VALUE}}',
    ],
];
```

---

## ⚠️ Windows vs Linux 差异

| 场景 | Linux | Windows |
|------|-------|---------|
| 启动方式 | `php gaia app` 直接 `Worker::runAll()` | Gaia 自动生成 `runtime/gaia/windows/start_*.php` 并通过 `proc_open` 管理 |
| 守护进程 | 支持 `-d` 参数 daemon 模式 | 不支持 `-d`，Windows 下为前台运行（可借助 WinSW/NSSM 注册为系统服务） |
| 路径分隔符 | 直接 `/` | **必须**用 `DIRECTORY_SEPARATOR`，禁止硬编码 `/` 或 `\` |
| 进程管理 | `posix_kill` / `Worker::stopAll()` | `proc_terminate` / `taskkill` |

---

## ✅ 交付前检查清单

**代码写完后必须逐项确认**：

- [ ] 每个新文件都有 `declare(strict_types=1);`
- [ ] Service 类使用 `use Instance` 单例 trait
- [ ] Process 类的 `$processConfig` 为**无类型**声明，且 `listen` 不为空（内部进程可用 `tcp://0.0.0.0:0`）
- [ ] Command 的 `$defaultName` / `$defaultDescription` / `$defaultGroup` 为**无类型**声明（父类均无类型，加类型会 Fatal Error）
- [ ] Process **未定义** `onWorkerStop()` 方法；关闭清理通过 `onWorkerStart` 中注册 `$worker->onWorkerStop` 闭包实现
- [ ] Process 已在 `AppCommand::$process` 注册
- [ ] 无静态属性存请求状态
- [ ] 回调方法（`onMessage` 等）业务逻辑外层有 `try-catch(\Throwable $e)`
- [ ] 日志用 `Logger::info/error/warning`，没有 `echo` 或 `file_put_contents`
- [ ] 异常消息包含上下文
- [ ] 运行 `php .agents/skills/gaia/scripts/lint.php` 通过

---

## 📚 详细规范参考

完整开发规范（目录结构图、命名规范、分层模型、Workerman 常驻内存红线、Code Review Checklist）：

→ [references/code-development-spec.md](references/code-development-spec.md)
