# Gaia 插件用法参考

> 本文是从各插件源码（`Install.php` / `install/config/*` / 核心类）提取的事实性参考。
> **不确定某个 API 时，以 `vendor/mongdch/<插件>/src/` 下的源码为准，禁止凭记忆臆造方法。**

---

## 0. 通用机制

### 0.1 `vendor:publish` 参数来源

`php gaia vendor:publish <ns>` 的实现（`gaia\command\VendorPublishCommand`）：

```php
$vendor = $args[0];                       // 命令行第一个参数，如 gaia\queue
$plugin = "\\{$vendor}\\Install";         // 拼接为 \gaia\queue\Install
if (!defined($plugin . '::GAIA_PLUGIN')) { /* 报错：The package not support Gaia! */ }
$callback = $plugin . '::publish';
```

即：**参数 = 插件 `composer.json` 中 `autoload.psr-4` 的 key（去掉尾部反斜杠）**，等价于插件 `src/Install.php` 的 `namespace`。

### 0.2 发布即覆盖

`support\Plugin::copydir()` / `copyFile()` 第三个参数 `$overwrite`，所有插件均传 **`true`**。
重新发布会覆盖用户已改过的 `config/`、`support/`、`app/` 文件 → 发布前必须提醒用户备份 / 先提交版本库。

### 0.3 独立应用启动脚本骨架

发布到 `bin/xxx.php` 的脚本统一结构：

```php
public function run()
{
    App::init($this->name);              // 应用名，决定 runtime 目录与配置隔离
    Plugin::register();                  // 扫描 support/**/Bootstrap.php 启动插件
    Gaia::instance()->runProcess($this->process);
}
```

因此都支持 Workerman 全系命令：`start` / `start -d` / `stop` / `restart` / `reload` / `status`。
Windows 不支持 `-d`，由框架生成 `runtime/gaia/windows/start_*.php` 后 `proc_open` 托管。

### 0.4 配置读取规则

子目录配置文件使用**点号**访问：`config/crontab/app.php` → `Config::instance()->get('crontab.app.driver')`；
`config/http/app.php` → `Config::instance()->get('http.app.workerman.config')`。

---

## 1. mon-http（HTTP 服务）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/mon-http` |
| 命名空间 | `mon\http\` |
| 发布命令 | `php gaia vendor:publish mon\http` |
| 启动 | `php bin/http.php start [-d]` |
| 依赖 | `workerman/workerman ^4.1\|\|^5.1`、`nikic/fast-route`、`mongdch/mon-util`、`mongdch/mon-log` |

### 1.1 发布产物

| 源文件 | 发布到项目 |
|--------|-----------|
| `src/install/http.php` | `bin/http.php` |
| `src/install/fpm.php` | `public/index.php` |
| `src/install/config/*` | `config/http/`（app / cors / firewall / middleware / session / throttle） |
| `src/install/support/*` | `support/http/`（process/Http.php、Route.php、Controller.php、ErrorHandler.php、middleware/、command/、extends/） |

### 1.2 核心配置 `config/http/app.php`

```php
return [
    'exception' => \support\http\ErrorHandler::class,
    'fpm'       => [
        'enable' => env('HTTP_FPM_ENABLE', false),
        'cache'  => RUNTIME_PATH . '/cache/fpm_route_cache.php',
    ],
    'workerman' => [
        'config' => [
            'listen'    => 'http://' . env('HTTP_LISTEN_HOST', '0.0.0.0') . ':' . env('HTTP_LISTEN_PORT', 8087),
            'context'   => [],
            'transport' => 'tcp',
            'count'     => env('HTTP_WORKER_COUNT', 0),   // 0 → cpuCount() * 2
            'reusePort' => false,
        ],
        'newCtrl'   => true,                    // 每次请求重新创建控制器，避免常驻内存状态残留
        'static'    => [
            'enable'   => env('HTTP_STATIC_ENABLE', false),
            'path'     => ROOT_PATH . '/public',
            'ext_type' => [],
        ],
    ],
];
```

### 1.3 路由 API（`mon\http\Route`）

```php
use mon\http\Route;

$route = Route::instance();

$route->get($pattern, $callback);       // 也支持 post/put/patch/delete/options/any
$route->map(['GET', 'POST'], $pattern, $callback);
$route->group($pattern, Closure $callback);   // $pattern 可为字符串或数组
$route->file($path, $root, $ext = [], $method = ['GET', 'POST']);
$route->error($callback);               // 路由未命中处理
$route->load($routePath, $recursive);   // 批量加载路由文件
$route->cache($path);                   // 路由缓存

// $pattern 数组形式（同时指定中间件与命名空间）
$route->post(['path' => '/test', 'middleware' => [MiddlewareA::class]], $callback);
$route->group(['path' => '/api', 'namespace' => '\app\controller\\', 'middleware' => CorsMiddleware::class], function ($route) {
    $route->get('/user', 'User@index');
});

// 命名路由（Router 返回对象）
$route->get('/test', [Index::class, 'test'])->name('test');
route('test');   // 助手函数获取路径
```

`map()` 返回 `mon\http\Router`（可链式调用 `->name()`）。
回调支持：闭包、`[类::class, 'method']`、`'User@index'`（配合 `namespace`）。

### 1.4 请求 `mon\http\Request`

```php
request()->get($name, $default, $filter);     // query
request()->post($name, $default);
request()->json($name, $default);
request()->header($name, $default);
request()->server() / session() / cookie() / file($name);
request()->method() / host() / path() / uri() / url() / fullUrl();
request()->ip() / protocolVersion() / isAjax();
request()->service();                          // 底层驱动对象
```

### 1.5 响应 `mon\http\Response`

继承 `Workerman\Protocols\Http\Response`。

```php
return ['code' => 1, 'data' => $list];   // 数组/字符串自动包装为响应
response(200, ['X-Token' => 'x'], 'ok'); // 助手函数
$response->withFile($file);
$response->file($file, $request);        // 带 304 协商缓存
$response->download($file, 'name.txt');
$response->exception($e);                // 绑定异常供 ErrorHandler 处理
redirect($url, 302);                     // 抛 JumpException
dump(...$args);                          // 抛 DumperException 调试
```

### 1.6 中间件

实现 `mon\http\interfaces\MiddlewareInterface`：

```php
public function process(RequestInterface $request, Closure $next): Response
{
    // 前置
    $response = $next($request);
    // 后置
    return $response;
}
```

注册位置 `config/http/middleware.php`：

```php
return [
    ''            => [],   // 全局
    'fpm'         => [],   // FPM 模式
    'workerman'   => [],   // Workerman 模式
    'static_file' => [],   // 静态资源
];
```

内置中间件（发布到 `support/http/middleware/`）：`CorsMiddleware`、`FirewallMiddleware`、`ThrottleMiddleware`
（限流算法：`throttle/CounterFixed`、`CounterSlider`、`TokenBucket`、`LeakyBucket`）。

运行期可 `mon\http\Middleware::load($config)` / `Middleware::set('workerman', [XxxMiddleware::class])`。

### 1.7 常驻内存注意

- `newCtrl => true` 保证控制器不被复用，**禁止**在控制器中保存请求级状态到属性并跨请求使用；
- 使用 ORM 时，进程内注册 `ORM::register(true)`，并把 `mon\thinkORM\ORMMiddleware` 加入 `workerman` 中间件组（自动清理 SQL 日志 + 回滚未提交事务）；
- 发布后新增路由请写在 `support/http/Route.php`，或自行在 `process\Http::registerRoute()` 中 `require`。

---

## 2. think-orm（数据库 / Dao）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/think-orm` |
| 命名空间 | `mon\thinkORM\` |
| 发布命令 | `php gaia vendor:publish mon\thinkORM` |
| 发布产物 | `config/database.php` |
| 依赖 | `topthink/think-orm ^2.0\|\|^3.0`、`mongdch/mon-util`、`mongdch/mon-log`、`mongdch/mon-cache` |

### 2.1 注册 ORM（常驻内存必须）

```php
use mon\thinkORM\ORM;

// $longLink=true 时注册 Timer 定时执行 SELECT 1 保活（默认 50s）
ORM::register(bool $longLink = false, array $config = [], ?LoggerInterface $log = null, ?CacheInterface $cache = null, int $timer = 50);
ORM::heart(int $timer = 50);   // 单独注册心跳
```

`ORM::register()` 会：读取 `Config::get('database')` → `Db::setConfig()` → 注入日志 → 若存在 `support\cache\CacheService` 则注入缓存 → 按需注册心跳。

### 2.2 关键配置 `config/database.php`

`default` / `connections.mysql.{type,hostname,database,username,password,hostport,charset,prefix,deploy,rw_separate,fields_strict,auto_timestamp,break_reconnect,fields_cache,trigger_sql,query}`。

> `trigger_sql` 默认 `false`：**开启后必须自行清理 SQL 日志，否则常驻进程内存持续增长**。
> `query` 固定为 `\mon\thinkORM\extend\Query::class`（Dao 依赖它）。

### 2.3 Dao 对象

```php
namespace app\dao;

use mon\thinkORM\Dao;

class UserDao extends Dao
{
    protected $table = 'user';        // 操作表名
    protected $connection = '';       // 连接配置节点，空则用默认
    protected $allow = ['name', 'mobile', 'status'];   // 允许操作字段白名单
}
```

自带方法：

```php
$dao->getTable() / getConnection() / getError();
$dao->db();                                   // 返回 mon\thinkORM\extend\Query
$dao->query($sql, $bind, $master);            // 原生查询
$dao->execute($sql, $bind);                   // 原生执行
$dao->scope($name, ...$args);                 // 查询作用域
$dao->allowField(['name']);                   // 限制字段，返回 Dao
$dao->get($format, $where, $query);           // 单条
$dao->all($format, $where, $query);           // 多条
$dao->save($data, $forceInsert, $getLastInsID, $query);   // 新增/更新
$dao->saveAll($data, $limit, $query);         // 批量写入
```

`__call` / `__callStatic` 透传给 `Query`，因此 think-orm 的链式方法全部可用：
`where / whereIn / whereOr / field / order / limit / page / join / group / having / cache / lock / json /
find / select / value / column / insert / insertGetId / insertAll / update / delete / chunk / cursor /
transaction / startTrans / commit / rollback / getLastSql / buildSql` 等。

trait 能力：`Validator`（内置验证器）、`AutoTime`（自动时间戳）、`AutoAttr`（修改器/获取器）、`BatchUpdate`（批量更新/批量增量）。

### 2.4 ORMMiddleware

`mon\thinkORM\ORMMiddleware` 请求结束后执行 `Db::getDbLog(true)` 清理 SQL 日志，并检查是否有未提交事务（有则 `rollBack()` 并抛 `RuntimeException`）。

### 2.5 常驻内存注意

- 每个需要 DB 的进程都要在 `onWorkerStart` 调 `ORM::register(true)`；
- 心跳 `Timer` 由插件注册，自定义 Process 仍需在 `$worker->onWorkerStop` 闭包清理自己注册的 `Timer`；
- 事务必须显式 `commit` / `rollback`，跨请求残留事务会触发 `ORMMiddleware` 异常。

---

## 3. mon-cache（缓存）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/mon-cache` |
| 命名空间 | `mon\cache\` |
| 发布命令 | `php gaia vendor:publish mon\cache` |
| 依赖 | `psr/simple-cache ^3.0`（`php >=8.0`） |

### 3.1 发布产物

| 源文件 | 发布到项目 |
|--------|-----------|
| `src/gaia/config.php` | `config/cache.php` |
| `src/gaia/Rdb.php` | `support/cache/extend/Rdb.php` |
| `src/gaia/Sqlite.php` | `support/cache/extend/Sqlite.php` |
| `src/gaia/CacheService.php` | `support/cache/CacheService.php` |

### 3.2 驱动

内置：`mon\cache\drivers\File`、`mon\cache\drivers\Redis`。
Gaia 扩展：`support\cache\extend\Rdb`（复用框架 RedisService 实例）、`support\cache\extend\Sqlite`（依赖 think-orm）。

`config/cache.php` 结构：`default`（默认驱动）、`keep_alive.{enable,ping,reset_max,event}`、`stores.{rdb,sqlite,file,redis}`。

自定义驱动需实现 `mon\cache\CacheInterface`，通过 `$cache->extend($name, $config)` 注册。

### 3.3 使用

```php
use support\cache\CacheService;

$cache = CacheService::instance();     // 单例，use Instance

$cache->get($key, $default);
$cache->set($key, $value, $ttl);
$cache->delete($key) / has($key) / clear();
$cache->getMultiple($keys, $default) / setMultiple($values, $ttl) / deleteMultiple($keys);
$cache->pull($key, $default);          // 读取并删除
$cache->inc($key, $step, $ttl) / dec($key, $step, $ttl);
$cache->store($type, $config, $reset); // 切换/重建驱动，返回 CacheInterface
$cache->getService();                  // 返回 mon\cache\Cache
```

### 3.4 断线自愈

`CacheService` 构造时若 `enable=true` 且处于 Workerman 环境（`IN_WORKERMAN`），注册 `Timer` 每 `ping` 秒执行一次 `ping()`；
连续失败次数 ≤ `reset_max` 时自动重建 `Cache` 实例，超过则 `unKeep()` 停止保活，并触发 `cache_error` 事件（可用 `mon\util\Event` 监听）。

---

## 4. gaia-crontab（定时任务）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/gaia-crontab` |
| 命名空间 | `gaia\crontab\` |
| 发布命令 | `php gaia vendor:publish gaia\crontab` |
| 启动 | `php bin/crontab.php start [-d]`（内部进程：`server` + `task`） |
| 依赖 | `workerman/crontab`、`mongdch/mon-util`、`mongdch/mon-env`、`mongdch/mon-log` |

### 4.1 发布产物

| 源文件 | 发布到项目 |
|--------|-----------|
| `src/install/crontab.php` | `bin/crontab.php` |
| `src/install/config/*` | `config/crontab/`（app.php、job.php） |
| `src/install/support/*` | `support/crontab/`（CrontabService.php、process/{Server,Task}.php、command/） |
| `src/install/task/*` | `app/crontab/`（DemoTask.php、ClearLogTask.php） |

### 4.2 任务定义

**数组驱动**（默认 `gaia\crontab\driver\Job`，读 `config/crontab/job.php`）：

```php
return [
    [
        'title'     => '清理日志',
        'type'      => 0,                                  // 0-对象方法 1-HTTP请求
        'rule'      => '0/10 * * * * *',                   // 6 位：秒 分 时 日 月 周
        'target'    => \app\crontab\CleanLogTask::class,   // 支持 "Class@method"，默认方法 handler
        'params'    => [],
        'singleton' => 0,                                  // 0-多次 1-单次
        'status'    => 1,                                  // 0-禁用 1-启用
    ],
];
```

HTTP 类型任务 `params` 支持：`method` / `header` / `data` / `timeout` / `ua`。

**Mysql 驱动**：改 `config/crontab/app.php` 的 `driver` 为 `\gaia\crontab\driver\Mysql::class`，
执行 `php gaia crontab:dbinit` 建 `crontab`、`crontab_log` 两张表。

主配置 `config/crontab/app.php`：`driver`、`lock_prefix`、`lock_expire`、`task_table`、`log_table`、`variable.{status,type,singleton}`、`process.{server,task}`（text 协议端口 7234/7235）、`log`。

### 4.3 任务类

```bash
php gaia crontab:make-task CleanLog     # 生成 app/crontab/CleanLogTask.php
```

```php
namespace app\crontab;

class CleanLogTask
{
    public function handler()
    {
        try {
            // 业务逻辑
            return 'ok';
        } catch (\Throwable $e) {
            \mon\log\Logger::error('清理日志失败', ['error' => $e->getMessage()]);
            return $e->getMessage();
        }
    }
}
```

> 默认入口方法名 **`handler`**；`handler()` 内必须 `try-catch`，异常外抛会影响调度进程。

### 4.4 运行时 API

```php
use support\crontab\CrontabService;

CrontabService::getPool();          // 当前运行中的任务池
CrontabService::reload([1, 2]);     // 重载指定任务 ID
CrontabService::communication('ping');
CrontabService::getManage();        // gaia\crontab\TaskManage 单例
```

`gaia\crontab\TaskManage`（单例，`use Instance`）提供：`getTaskList()` / `getTask($id)` /
`finishSingletonTask($id)` / `updateTaskRunning($id, $time, $times)` / `recordTaskLog($log)`。
自定义驱动实现 `gaia\crontab\TaskInterface` 并配置到 `crontab.app.driver`。

---

## 5. gaia-queue（消息队列）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/gaia-queue` |
| 命名空间 | `gaia\queue\` |
| 发布命令 | `php gaia vendor:publish gaia\queue` |
| 启动 | `php bin/queue.php start [-d]` |
| 驱动 | Redis |

### 5.1 发布产物

| 源文件 | 发布到项目 |
|--------|-----------|
| `src/install/queue.php` | `bin/queue.php` |
| `src/install/config/*` | `config/queue/`（app.php、queue.php） |
| `src/install/support/*` | `support/queue/`（QueueService.php、process/Queue.php、command/） |
| `src/install/consumers/*` | `app/queue/`（DemoQueue、EmailQueue、HttpQueue） |

### 5.2 配置

`config/queue/queue.php`（Redis 连接）：

```php
return [
    'default'     => 'default',
    'connections' => [
        'default' => [
            'scheme'        => 'redis',
            'host'          => env('QUEUE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port'          => env('QUEUE_PORT', env('REDIS_PORT', 6379)),
            'auth'          => env('QUEUE_AUTH', env('REDIS_AUTH', '')),
            'prefix'        => env('QUEUE_PREFIX', env('REDIS_PREFIX', '')),
            'database'      => env('QUEUE_DB', 5),
            'max_attempts'  => env('QUEUE_ATTEMPTS', 5),   // 失败重试次数
            'retry_seconds' => env('QUEUE_RETRY', 5),      // 重试间隔（秒）
        ],
    ],
];
```

`config/queue/app.php`：`consumers_path`（默认 `app/queue/`）、`namespace`（默认 `\app\queue`）、
`handler_driver`（默认 `gaia\queue\driver\Logger`，可选 Mysql）、`log_table`、`process.listen`（text://127.0.0.1:7123）、`log`。

### 5.3 消费者

`app/queue/` 下的类会被**自动扫描注册**，无需手动挂载；同一「连接 + 队列名」不可重复（重复抛 `RuntimeException`）。

```bash
php gaia queue:make-queue Order      # 生成 app/queue/OrderQueue.php
```

必须实现 `gaia\queue\ConsumerInterface`：`connection()`、`queue()`、`describe()`、`consume($data)`；
可选实现 `onConsumeFailure(Throwable $e, array $package)`、`onConsumeSuccess($result, array $package)`。

约定：消费者类 `use mon\util\Instance;`（框架通过 `Container::instance()->get($class)` 实例化）。

### 5.4 投递

```php
use support\queue\QueueService;

// 同步：直接写 Redis，FPM / CLI 均可用（delay 走 zAdd 延迟集合，否则 lPush 到 QUEUE_WAITING{queue}）
QueueService::syncSend(string $queue, array $data, int $delay = 0, string $connect = '', int $ping = 55): bool;

// 异步：需要 Workerman 环境，经队列进程转发
QueueService::asyncSend(string $queue, array $data, int $delay = 0, string $connect = '');

QueueService::getPool();                  // 运行中队列
QueueService::getQueue();                 // 扫描到的所有消费者定义
QueueService::connection($name);          // 获取 gaia\queue\QueueClient
QueueService::getQueueConfig($connect);   // 供 RedisService::tryExecCommand 使用
```

### 5.5 常驻内存注意

- `consume()` 内**必须** `try-catch(\Throwable)`，异常会走失败回调并按 `max_attempts` / `retry_seconds` 重试；
- 消费进程常驻，禁止在消费者对象属性中累计请求级状态（用 Redis / Dao 持久化）；
- 投递端若在常驻进程中用 `syncSend`，依赖 `app\service\RedisService` 的 `tryExecCommand` 保活。

---

## 6. gaia-channel（跨进程通信）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/gaia-channel` |
| 命名空间 | `gaia\channel\` |
| 发布命令 | `php gaia vendor:publish gaia\channel` |
| 启动 | `php bin/channel.php start [-d]` |
| 默认监听 | `frame://0.0.0.0:2206`（`CHANNEL_PORT`），进程数必须为 1 |

### 6.1 发布产物

`bin/channel.php`、`support/channel/{Bootstrap.php,ChannelService.php,process/Channel.php,command/ChannelCommand.php}`。

生成监听进程模板：`php gaia make:channel <名称>` → `support/process/channel/<Name>.php`
（模板内含 `Channel\Client::connect()` + `Client::on()` 监听骨架，需自行注册到 `AppCommand::$process`）。

### 6.2 API

```php
use support\channel\ChannelService;

$channel = ChannelService::instance();   // 构造时自动 Client::connect(host, port)

// 广播模型：所有监听者都收到
$channel->on($event, callable $callback);
$channel->publish($event, array $data);
$channel->unsubscribe($event);

// 队列模型：只有一个监听者收到
$channel->watch($event, callable $callback);
$channel->enqueue($event, array $data);
$channel->unwatch($event);
```

地址来源：`support\channel\process\Channel::getListenHost()` / `getListenPort()`（来自 `ProcessTrait`，带 static 缓存），
`0.0.0.0` 会自动替换为 `127.0.0.1`。

### 6.3 注意

- **必须先启动 `bin/channel.php`**，否则 `ChannelService::instance()` 连接失败；
- 事件回调中同样需要 `try-catch`，异常会中断监听循环；
- 广播事件数据必须是数组（`publish(string $event, array $data)`）。

---

## 7. gaia-gateway（WebSocket）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/gaia-gateway` |
| 命名空间 | `gaia\gateway\` |
| 发布命令 | `php gaia vendor:publish gaia\gateway` |
| 启动 | `php bin/gateway.php start [-d]`（进程：`register` + `business` + `gateway`） |
| 依赖 | `workerman/gateway-worker` |

### 7.1 发布产物

| 源文件 | 发布到项目 |
|--------|-----------|
| `src/gateway.php` | `bin/gateway.php` |
| `src/config/*` | `config/gateway/`（app / gateway / business / register） |
| `src/support/*` | `support/gateway/`（GatewayService.php、process/{Register,Business,Gateway}.php） |
| `src/gateway/Event.php` | `app/gateway/Event.php` |

### 7.2 配置要点

| 文件 | 关键项 |
|------|--------|
| `config/gateway/gateway.php` | `config.listen`（默认 `websocket://0.0.0.0:12301`）、`config.count`（0=CPU 核数）、`config.reloadable=false`、`property.{lanIp,startPort(4900),pingInterval,pingData,pingNotResponseLimit,transport,secretKey}` |
| `config/gateway/business.php` | `config.count`、`property.eventHandler`（默认 `\app\gateway\Event::class`）、`property.secretKey` |
| `config/gateway/register.php` | `config.listen`（`text://127.0.0.1:1236`，count 必须为 1）、`property.secretKey` |
| `config/gateway/app.php` | 日志通道 `gateway` 配置 |

### 7.3 事件类

`app/gateway/Event.php` 静态方法：`onWorkerStart(BusinessWorker $worker)`、`onConnect(string $client_id)`、
`onWebSocketConnect(string $client_id, array $data)`、`onMessage(string $client_id, string $message)`、`onClose(string $client_id)`。

`onWorkerStart` 中初始化日志通道与 `ORM::register(true)`。

### 7.4 发送 API（`support\gateway\GatewayService`）

单例（`use Instance`），`__call` 透传 `GatewayWorker\Lib\Gateway`；构造时自动设置
`Gateway::$registerAddress = Register::getListenHost() . ':' . Register::getListenPort()`。

```php
$gw = GatewayService::instance();
$gw->sendToCurrentClient($msg, $raw = false);
$gw->sendToClient($client_id, $msg);
$gw->sendToUid($uid, $msg);
$gw->sendToGroup($group, $msg, $exclude = null);
$gw->sendToAll($msg, $client_id_array = null, $exclude = null);
$gw->bindUid($client_id, $uid) / unbindUid($client_id, $uid);
$gw->joinGroup($client_id, $group) / leaveGroup($client_id, $group) / ungroup($group);
$gw->isOnline($client_id) / isUidOnline($uid);
$gw->getClientIdByUid($uid) / getUidByClientId($client_id);
$gw->setSession($client_id, $session) / updateSession() / getSession();
$gw->getAllClientIdList() / getAllClientIdCount() / getAllUidList() / getAllGroupIdList();
$gw->closeClient($client_id, $msg = null) / destoryCurrentClient();
```

> 注意：经 `GatewayService` 主动发送的数据**不会经过 `Event.php`**，而是由 Gateway 进程直接转发给客户端。

---

## 8. gaia-nat（内网穿透）

| 项 | 值 |
|----|----|
| 包名 | `mongdch/gaia-nat` |
| 命名空间 | `gaia\nat\` |
| 发布命令 | `php gaia vendor:publish gaia\nat` |
| 启动 | `php bin/nat_server.php start -d`（服务端） / `php bin/nat_client.php start -d`（客户端） |
| 依赖 | `workerman/channel ^1.2` |

### 8.1 发布产物

- `bin/nat_server.php`（进程：`nat-channel` + `nat-server`）
- `bin/nat_client.php`（进程：`nat-client`）
- `support/nat/process/{Channel,NatServer,NatClient}.php`
- `config/nat.php` —— **特殊**：发布时用 `sprintf()` 注入随机生成的 24 位 token（不覆盖已存在文件）。

### 8.2 配置

```php
return [
    'debug'         => env('NAT_DEBUG', false),
    'token'         => env('NAT_TOKEN', '<发布时自动生成的随机 token>'),   // 两端必须一致
    'channel_host'  => env('NAT_CHANNEL_HOST', 'nat.gdmon.com'),          // 服务端通信地址
    'channel_port'  => env('NAT_CHANNEL_PORT', 2209),
    'proxy_host'    => env('NAT_PROXY_HOST', '127.0.0.1'),                // 客户端：本地被穿透服务
    'proxy_port'    => env('NAT_PROXY_PORT', 80),
    'event'         => [ /* client_register / server_register / client_proxy_msg / client_proxy_close
                            out_net_connect / out_net_msg / out_net_close */ ],
    'server'        => [
        'listen' => 'tcp://127.0.0.1:' . env('NAT_SERVER_PORT', 8087),
        'count'  => env('NAT_SERVER_COUNT', 1),
    ],
];
```

### 8.3 使用要点

1. 服务端与客户端使用**同一份 `token`**；
2. 服务端 `listen` 为 `tcp` 协议（支持 http / https / tcp 三种穿透），可只监听 `127.0.0.1` 再用 nginx 反向代理；
3. nginx 反代需设置 `proxy_http_version 1.1` + `proxy_set_header Connection ""`；
4. 客户端 `proxy_host` / `proxy_port` 指向本地真实服务，配合 nginx 多域名可实现完全内网透传。

---

## 9. 典型组合场景

| 场景 | 组合 |
|------|------|
| HTTP 接口 + 数据库 | `mon-http` + `think-orm`（`ORM::register(true)` + `ORMMiddleware`） |
| 接口异步化 | `mon-http` 中 `QueueService::syncSend()` 投递 → `gaia-queue` 消费 |
| 定时统计报表 | `gaia-crontab`（Task 类）+ `think-orm`（Dao）+ `mon-cache`（结果缓存） |
| 实时推送 | `gaia-gateway`（WebSocket）+ `gaia-channel`（业务进程广播事件 → Gateway 推送） |
| 多进程共享事件 | `gaia-channel` 广播，各进程 `on()` 监听 |
| 本地调试外网回调 | `gaia-nat` 暴露本地 `mon-http` 的 8087 端口 |
