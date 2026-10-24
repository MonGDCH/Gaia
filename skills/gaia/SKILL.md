---
name: gaia
description: 为 Gaia Workerman 框架开发 PHP CLI 常驻内存服务 当用户要求创建进程 服务 指令 注册业务逻辑 使用 HTTP ORM 缓存 队列 定时任务 跨进程通信 WebSocket 内网穿透等插件能力 或调试 Workerman 常驻内存问题时使用
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
| `HTTP` / `接口` / `路由` / `中间件` / `Controller` | "用 Gaia 写个登录接口" |
| `mon-http` / `vendor:publish` / `插件` | "mon-http 怎么发布配置？" |
| `ORM` / `Dao` / `数据库` / `查询` / `事务` | "写个 UserDao 查询用户" |
| `缓存` / `Cache` / `mon-cache` | "用缓存存验证码" |
| `队列` / `Queue` / `消费者` / `延迟队列` / `消息` | "订单支付后发个队列消息" |
| `定时任务` / `定时` / `Crontab` / `Cron` | "每天凌晨清理日志" |
| `跨进程` / `进程通信` / `Channel` / `广播` / `发布订阅` | "多进程间广播事件" |
| `WebSocket` / `长连接` / `Gateway` / `推送` | "写个 WebSocket 聊天室" |
| `内网穿透` / `NAT` / `外网访问` | "把本地服务穿透到外网" |

### 触发示例

**用户说**："帮我写一个 Gaia 进程，监听 9000 端口消费 Redis 队列"
→ **触发**："Gaia" + "进程" → 生成 Process 代码 + 注册到 AppCommand

**用户说**："新增一个 Redis 锁的 Service"
→ **触发**："Gaia Service" → 生成 Service 骨架 + 单例模式

**用户说**："php gaia app 报未定义启动进程"
→ **触发**："php gaia" → 检查 AppCommand::$process 是否为空

**用户说**："Workerman 的 Timer 要清理吗？"
→ **触发**："Workerman" + "Timer" → 告知红线规则 + 代码示例

**用户说**："用 Gaia 写个用户登录接口"
→ **触发**："HTTP" + "接口" → `mon-http` 插件 → 路由 + 控制器 + 中间件

**用户说**："订单支付成功后异步发短信"
→ **触发**："队列" → `gaia-queue` 插件 → 消费者 + `QueueService::syncSend`

**用户说**："每天凌晨 2 点清理过期订单"
→ **触发**："定时任务" → `gaia-crontab` 插件 → Task 类 + job 配置

**用户说**："多个进程之间怎么广播消息"
→ **触发**："跨进程" → `gaia-channel` 插件 → `ChannelService::publish/on`

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
| 查看全部路由 | `php gaia route:show` |
| 统一启动全部进程 | `php gaia app` |
| 代码规范检查 | `php skills/gaia/scripts/lint.php` |
| 安装插件 | `composer require mongdch/<插件包名>` |
| 发布插件资源 | `php gaia vendor:publish <发布命名空间>` |
| 启动插件服务 | `php bin/<插件>.php start [-d]` |

---

## 🔌 插件能力索引（需求 → 插件）

**遇到以下需求时，优先使用对应插件，禁止自己从零手撸实现。**

| 需求 | 插件 | composer 包名 | 发布命名空间 | 核心类 / 入口 |
|------|------|---------------|--------------|---------------|
| HTTP 接口、路由、中间件、Session | mon-http | `mongdch/mon-http` | `mon\http` | `mon\http\Route`、`support\http\process\Http`、`bin/http.php` |
| 数据库查询、事务、Dao | think-orm | `mongdch/think-orm` | `mon\thinkORM` | `mon\thinkORM\Dao`、`mon\thinkORM\Db`、`mon\thinkORM\ORM::register()` |
| 缓存读写 | mon-cache | `mongdch/mon-cache` | `mon\cache` | `support\cache\CacheService::instance()` |
| 异步任务、削峰、延迟执行 | gaia-queue | `mongdch/gaia-queue` | `gaia\queue` | `gaia\queue\ConsumerInterface`、`support\queue\QueueService`、`bin/queue.php` |
| 定时执行任务 | gaia-crontab | `mongdch/gaia-crontab` | `gaia\crontab` | `app\crontab\XxxTask`、`config/crontab/job.php`、`bin/crontab.php` |
| 跨进程事件广播 / 事件队列 | gaia-channel | `mongdch/gaia-channel` | `gaia\channel` | `support\channel\ChannelService::instance()`、`bin/channel.php` |
| WebSocket 推送、长连接 | gaia-gateway | `mongdch/gaia-gateway` | `gaia\gateway` | `app\gateway\Event`、`support\gateway\GatewayService`、`bin/gateway.php` |
| 本地服务暴露到外网 | gaia-nat | `mongdch/gaia-nat` | `gaia\nat` | `bin/nat_server.php`、`bin/nat_client.php` |

**插件接入三步曲**（任何插件都按这个顺序）：

```bash
composer require mongdch/<包名>          # 1. 安装
php gaia vendor:publish <发布命名空间>    # 2. 发布配置/进程/示例代码
php bin/<插件>.php start [-d]            # 3. 启动（或把进程注册到 AppCommand::$process）
```

> 完整插件用法（配置项、API 清单、常驻内存注意事项、典型场景代码）见
> → [references/plugins.md](references/plugins.md)

---

## 📁 项目目录结构（业务代码落位）

**所有业务代码必须写在 `app/` 下，禁止在 `support/` 写业务代码。** 新建文件前先按下表确定落位：

```
├── app/                            # 业务代码（命名空间前缀 app\）
│   ├── command/                    # 自定义命令行（php gaia xxx），*Command.php
│   ├── contract/                   # 契约层：枚举、接口、业务异常
│   │   ├── enum/                   # 枚举（app\contract\enum）
│   │   ├── exception/              # 业务自定义异常，*Exception.php
│   │   └── interface/              # 业务接口（按模块分子目录，如 wms/）
│   ├── crontab/                    # 定时任务，*Task.php，入口方法统一 handler()
│   ├── dao/                        # 数据访问层，*Dao.php（按业务模块分子目录）
│   │   ├── product/  scm/  wms/  pda/
│   │   └── AdminDao.php ...        # 系统级 Dao 放根目录
│   ├── http/                       # HTTP 接入层
│   │   ├── controller/             # admin/、pda/ 分端，再按模块分子目录
│   │   ├── middleware/             # admin/、pda/ 分端
│   │   ├── routes/                 # 路由定义（index.php、admin.php、pda.php + admin/ 模块文件）
│   │   └── view/                   # 服务端渲染视图（admin/ 按模块分目录）
│   ├── process/                    # 自定义 Workerman 进程（默认空，仅保留 .gitignore）
│   ├── queue/                      # 队列消费者，*Queue.php
│   ├── service/                    # 业务服务层，*Service.php
│   ├── validate/                   # 验证器，*Validate.php（按模块分子目录）
│   └── helpers.php                 # 全局助手函数（谨慎新增）
```

### 落位决策表

| 要写的东西 | 放哪里 | 说明 |
|-----------|--------|------|
| HTTP 接口 | `app/http/controller/[端]/[模块]/XxxController.php` + `app/http/routes/[端].php` | **路由禁止写在 `support/http/Route.php`**，该文件只做 `Route::load(APP_PATH . '/http/routes')` |
| 鉴权/限流/上下文 | `app/http/middleware/[端]/XxxMiddleware.php` | 在 `config/http/middleware.php` 或路由 `group(['middleware' => ...])` 挂载 |
| 页面模板 | `app/http/view/[端]/[模块]/` | `support/http/extends/Template.php` 的 `$view_path` 指向 `app/http/view/` |
| SQL / 单表操作 | `app/dao/[模块]/XxxDao.php` | `use Instance`，`XxxDao::instance()` |
| 入参/数据校验 | `app/validate/[模块]/XxxValidate.php` | 继承 `mon\util\Validate`，定义 `$rule` / `$message` / `$scope` |
| 跨表业务编排 | `app/service/XxxService.php` | 默认 `use Instance` |
| 枚举/常量 | `app/contract/enum/XxxEnum.php` | 原生 `enum`，实现 `text()` 等方法 |
| 业务异常 | `app/contract/exception/XxxException.php` | 继承内置异常或 `BusinessException` |
| CLI 指令 | `app/command/XxxCommand.php` | 业务逻辑委托 Service |
| 常驻进程 | `app/process/XxxProcess.php` | **必须注册到 `AppCommand::$process`** |
| 定时任务 | `app/crontab/XxxTask.php` | 入口方法固定 `handler()` |
| 队列消费者 | `app/queue/XxxQueue.php` | 实现 `ConsumerInterface`，自动扫描 |

> 新增目录层级（如按模块分子目录）时，命名空间必须同步带上子目录：
> `app/dao/wms/StockDao.php` → `namespace app\dao\wms;`

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

composer.json 配置 `"": "./"`，文件路径直接对应完全限定名（**含子目录**）：

| 文件路径 | 完全限定名 |
|----------|-----------|
| `app/command/CleanCacheCommand.php` | `app\command\CleanCacheCommand` |
| `app/contract/enum/OrderEnum.php` | `app\contract\enum\OrderEnum` |
| `app/contract/exception/StockException.php` | `app\contract\exception\StockException` |
| `app/contract/interface/wms/StockInterface.php` | `app\contract\interface\wms\StockInterface` |
| `app/dao/OrderDao.php` | `app\dao\OrderDao` |
| `app/dao/wms/StockDao.php` | `app\dao\wms\StockDao` |
| `app/http/controller/admin/UserController.php` | `app\http\controller\admin\UserController` |
| `app/http/middleware/admin/LoginMiddleware.php` | `app\http\middleware\admin\LoginMiddleware` |
| `app/process/WorkerProcess.php` | `app\process\WorkerProcess` |
| `app/queue/OrderQueue.php` | `app\queue\OrderQueue` |
| `app/service/OrderService.php` | `app\service\OrderService` |
| `app/validate/OrderValidate.php` | `app\validate\OrderValidate` |
| `support/Plugin.php` | `support\Plugin` |

**反例**：`app/dao/wms/StockDao.php` 里写 `namespace app\dao;` ❌（子目录必须体现在命名空间）

### 规则 3：分层依赖方向（单向）【强制】

```
http/controller ─┐
http/middleware ─┼─► service ──► dao ──► think-orm(Db)
command ─────────┤                 │
process ─────────┤                 └──► validate
crontab / queue ─┘
        ↓
     contract（enum / exception / interface，各层均可依赖）
```

| 分层 | 放什么 | 禁止 |
|------|--------|------|
| `http/controller` | 接参、校验、调 Service、返回响应 | 写 SQL、直接操作 Redis |
| `http/middleware` | 鉴权、限流、上下文注入 | 承载业务逻辑 |
| `service` | 跨 Dao 编排、外部能力封装 | 依赖 controller / process |
| `dao` | 单表数据访问 + 内置验证器 | 跨表复杂编排 |
| `validate` | 规则 / 提示 / 场景 | 查库 |

- `Service` 类**默认**使用 `use mon\util\Instance;`（单例）；确需每次新建实例（带请求级状态）时可不使用。
- `Dao` 类同样 `use Instance`，对外通过 `XxxDao::instance()` 调用。
- 禁止 `dao` 依赖 `service`、禁止 `service` 依赖 `http/*` 或 `process`。

**反例**：

```php
// ❌ Service 反向依赖 Command / Controller
namespace app\service;
use app\command\AppCommand;
use app\http\controller\IndexController;
```

### 规则 4：类角色后缀与落位【强制】

| 角色 | 目录 | 后缀 | 必须继承 / 实现 |
|------|------|------|----------------|
| Command | `app/command/` | `Command` | `mon\console\Command` |
| Controller | `app/http/controller/[端]/[模块]/` | `Controller` | `support\http\Controller` |
| Middleware | `app/http/middleware/[端]/` | `Middleware` | `mon\http\interfaces\MiddlewareInterface` |
| Service | `app/service/` | `Service` | 无（默认 `use Instance`） |
| Dao | `app/dao/[模块]/` | `Dao` | `mon\thinkORM\Dao` + `use Instance` |
| Validate | `app/validate/[模块]/` | `Validate` | `mon\util\Validate` |
| Enum | `app/contract/enum/` | `Enum` | 原生 `enum`（`: string` / `: int`） |
| 业务异常 | `app/contract/exception/` | `Exception` | 内置异常或 `mon\http\exception\BusinessException` |
| 接口契约 | `app/contract/interface/[模块]/` | 无强制 | `interface` |
| 定时任务 | `app/crontab/` | `Task` | 无（入口方法固定 `handler()`） |
| 队列消费者 | `app/queue/` | `Queue` | `gaia\queue\ConsumerInterface` |
| Process | `app/process/` | 推荐 `Process` | `gaia\Process` |

**反例**：

```php
class RedisOperate { }                       // ❌ 没有 Service 后缀
class Foo extends Command { }                // ❌ 没有 Command 后缀
app/service/OrderDao.php                     // ❌ Dao 错放到 service 目录
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

### 规则 6：Process 必须注册到 AppCommand【强制】

**新建进程（含插件提供的进程）优先统一注册到 `app/command/AppCommand.php` 的 `$process`**，由 `php gaia app` 统一启停：

```php
class AppCommand extends Command
{
    // ↓↓↓ 在这里注册所有要启动的进程 ↓↓↓（无类型声明）
    protected $process = [
        // 插件进程
        'http'           => \support\http\process\Http::class,
        'queue'          => \support\queue\process\Queue::class,
        'crontab-server' => \support\crontab\process\Server::class,
        'crontab-task'   => \support\crontab\process\Task::class,
        'channel'        => \support\channel\process\Channel::class,
        // 自定义进程
        'worker'         => \app\process\WorkerProcess::class,
    ];
}
```

**规则要点**：

| 项 | 要求 |
|----|------|
| 注册位置 | `app/command/AppCommand.php::$process`（**唯一入口**，优先方案） |
| 进程名 | 小写短横线，如 `crontab-server`、`message-worker` |
| 独立部署 | 仅当需要独立伸缩/独立重启时，才用 `bin/xxx.php`（`php gaia make:bin` 生成） |
| 禁止 | 同一进程既注册到 `AppCommand` 又用 `bin/xxx.php` 启动（端口冲突） |

**不注册的后果**：`php gaia app` 报「未定义启动进程!」然后退出，或该进程根本不会启动。

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

### 规则 11：插件使用规则【强制】

1. **禁止造轮子**：需求命中「插件能力索引」时，**必须**先安装并 `vendor:publish` 对应插件，再基于插件扩展业务；禁止手写 HTTP 路由分发、Redis 队列消费、Cron 表达式解析等插件已提供的能力。
2. **禁止手抄配置**：插件配置一律来自 `vendor:publish` 发布的 `config/<插件>/*.php`，**禁止**自己新建同名配置文件或凭记忆写配置项。改配置优先改 `.env` 环境变量。
3. **发布会覆盖**：`vendor:publish` 为**强制覆盖**（`$overwrite = true`）。提醒用户：插件升级后重新发布会覆盖已修改的 `config/`、`support/`、`app/` 文件。
4. **进程统一注册**：插件进程优先注册到 `AppCommand::$process`，由 `php gaia app` 统一管理；仅「独立部署/独立伸缩」场景才用插件自带的 `bin/xxx.php`。**同一进程不要两处都启动**，否则端口冲突。
5. **插件代码同样受常驻内存红线约束**：`ORM::register(true)`、`CacheService` 保活、`gaia-queue` 消费进程内部都会注册 `Timer`，自定义 Process 中如调用它们，仍需在 `$worker->onWorkerStop` 闭包中做清理。
6. **消费者/任务类必须 try-catch**：`gaia\queue\ConsumerInterface::consume()`、`app\crontab\*Task::handler()` 内部**必须**捕获 `\Throwable` 并用 `Logger` 记录，异常外抛会导致消费进程/调度进程异常。
7. **命名空间来自 composer**：`vendor:publish` 的参数 = 插件 `composer.json` 中 `autoload.psr-4` 的 key（去掉尾部反斜杠），例如 `mon\http`、`gaia\queue`。不确定时读插件包的 `src/Install.php` 顶部 `namespace`。

---

## 🧱 标准骨架代码

### HTTP Controller 骨架

```php
<?php

declare(strict_types=1);

namespace app\http\controller\{{MODULE}};

use mon\http\Request;
use support\http\Controller;
use app\service\{{NAME}}Service;

/**
 * {{DESC}} 控制器
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Controller extends Controller
{
    /**
     * 列表
     *
     * @param Request $request 请求实例
     * @return \mon\http\Response
     */
    public function index(Request $request)
    {
        $data = $request->json();
        $result = {{NAME}}Service::instance()->getList($data);

        return $this->success('ok', $result);
    }

    /**
     * 详情（路由参数按名称注入）
     *
     * @param Request $request 请求实例
     * @param string $id 记录ID
     * @return \mon\http\Response
     */
    public function detail(Request $request, string $id)
    {
        $info = {{NAME}}Service::instance()->getInfo((int) $id);

        return $this->success('ok', $info);
    }
}
```

> 控制器基类 `support\http\Controller` 提供：`$this->success($msg, $data)` / `$this->error($msg)` /
> `$this->text()` / `$this->json()` / `$this->file()` / `$this->download()` / `$this->fetch()` / `$this->validate()`。
> 路由参数按**参数名**注入，类型化对象参数由容器解析。

### 路由定义骨架

```php
<?php
/*
|--------------------------------------------------------------------------
| 定义 {{DESC}} 路由
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

use mon\http\Route;
use app\http\controller\{{MODULE}}\{{NAME}}Controller;

Route::instance()->group('/{{PREFIX}}', function (Route $route) {
    $route->get('', [{{NAME}}Controller::class, 'index'])->name('{{PREFIX}}.index');
    $route->get('/detail/{id}', [{{NAME}}Controller::class, 'detail'])->name('{{PREFIX}}.detail');
});
```

### Dao 骨架

```php
<?php

declare(strict_types=1);

namespace app\dao\{{MODULE}};

use mon\thinkORM\Dao;
use mon\util\Instance;
use app\validate\{{MODULE}}\{{NAME}}Validate;

/**
 * {{DESC}} 数据访问对象
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Dao extends Dao
{
    use Instance;

    /**
     * 操作表名（不含前缀）
     *
     * @var string
     */
    protected $table = '{{TABLE}}';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 验证器
     *
     * @var string
     */
    protected $validate = {{NAME}}Validate::class;

    /**
     * 查询条件构造（列表/导出复用）
     *
     * @param array $data 筛选条件
     * @return \mon\thinkORM\extend\Query
     */
    public function queryScope(array $data = [])
    {
        $query = $this->db();
        if (isset($data['id']) && $this->validate()->int($data['id'])) {
            $query->where('id', intval($data['id']));
        }
        if (isset($data['status']) && $this->validate()->int($data['status'])) {
            $query->where('status', intval($data['status']));
        }

        return $query->order('id', 'desc');
    }
}
```

### Validate 骨架

```php
<?php

declare(strict_types=1);

namespace app\validate\{{MODULE}};

use mon\util\Validate;

/**
 * {{DESC}} 验证器
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Validate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'id'     => ['required', 'id'],
        'name'   => ['required', 'str', 'length:1,50'],
        'status' => ['required', 'in:0,1'],
    ];

    /**
     * 错误提示
     *
     * @var array
     */
    public $message = [
        'id'     => '参数异常',
        'name'   => '名称格式错误',
        'status' => '状态值错误',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        'add'  => ['name', 'status'],
        'edit' => ['id', 'name', 'status'],
    ];
}
```

### Enum 骨架

```php
<?php

declare(strict_types=1);

namespace app\contract\enum;

/**
 * {{DESC}} 枚举
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
enum {{NAME}}Enum: int
{
    /** 禁用 */
    case Status_Disable = 0;

    /** 启用 */
    case Status_Enable = 1;

    /**
     * 获取文本描述
     *
     * @return string
     */
    public function text(): string
    {
        return match ($this) {
            self::Status_Disable => '禁用',
            self::Status_Enable  => '启用',
        };
    }
}
```

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

**目录与分层**：

- [ ] 所有业务文件都在 `app/` 下，未在 `support/` 写业务代码
- [ ] 文件落位符合「目录结构 / 落位决策表」（Controller、Dao、Validate、Enum、Exception 各归其位）
- [ ] 命名空间与目录层级完全一致（含模块子目录，如 `app\dao\wms\`）
- [ ] 依赖方向单向：`controller → service → dao → Db`，未出现反向或同层循环依赖
- [ ] 路由写在 `app/http/routes/*.php`，未写进 `support/http/Route.php`
- [ ] `Dao` / `Validate` / `Enum` 等按模块放在对应子目录

**基础规范**：

- [ ] 每个新文件都有 `declare(strict_types=1);`
- [ ] Service 类使用 `use Instance` 单例 trait（或有明确理由不用）
- [ ] Dao 类使用 `use Instance`，对外通过 `XxxDao::instance()` 调用
- [ ] Process 类的 `$processConfig` 为**无类型**声明，且 `listen` 不为空（内部进程可用 `tcp://0.0.0.0:0`）
- [ ] Command 的 `$defaultName` / `$defaultDescription` / `$defaultGroup` 为**无类型**声明（父类均无类型，加类型会 Fatal Error）
- [ ] Process **未定义** `onWorkerStop()` 方法；关闭清理通过 `onWorkerStart` 中注册 `$worker->onWorkerStop` 闭包实现
- [ ] 新进程已在 `AppCommand::$process` 注册（与 `bin/xxx.php` 二选一）
- [ ] 无静态属性存请求状态
- [ ] 回调方法（`onMessage` 等）业务逻辑外层有 `try-catch(\Throwable $e)`
- [ ] 日志用 `Logger::info/error/warning`，没有 `echo` 或 `file_put_contents`
- [ ] 异常消息包含上下文
- [ ] 运行 `php skills/gaia/scripts/lint.php` 通过

**使用了插件时额外确认**：

- [ ] 插件已执行 `composer require` + `php gaia vendor:publish <命名空间>`，配置来自发布文件而非手写
- [ ] 插件进程只在一处启动（`bin/xxx.php` 或 `AppCommand::$process`，不重复）
- [ ] 队列消费者实现 `gaia\queue\ConsumerInterface` 且放在 `app/queue/`；`consume()` 内有 `try-catch`
- [ ] 定时任务类放在 `app/crontab/`，默认入口方法为 `handler()`；`handler()` 内有 `try-catch`
- [ ] 常驻内存进程用到 ORM 时调用了 `ORM::register(true)`；用到 HTTP 时注册了 `ORMMiddleware`
- [ ] 已提醒用户 `vendor:publish` 会覆盖已修改的 `config/` / `support/` / `app/` 文件

---

## 📚 详细规范参考

完整开发规范（目录结构图、命名规范、分层模型、Workerman 常驻内存红线、Code Review Checklist）：

→ [references/code-development-spec.md](references/code-development-spec.md)

插件完整用法（安装/发布/配置项/API 清单/常驻内存注意事项/典型场景）：

→ [references/plugins.md](references/plugins.md)

插件代码模板（队列消费者、定时任务、Dao、HTTP 控制器、HTTP 中间件、Gateway 事件）：

→ [assets/](assets/)
