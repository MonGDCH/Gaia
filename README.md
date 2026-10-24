# Gaia

命名源于`盖亚奥特曼`的基于`Workerman`的多进程服务管理框架

- `php`高性能`CLI`运行模式进程管理
- `windows`环境下`workerman`多进程支持


### 安装

```bash
composer create-project mongdch/gaia
```

---

## 插件生态

框架采用「内核 + 插件」的方式组织能力，内核（`mongdch/gaia-framework`）只负责进程/指令/配置的生命周期管理，
HTTP、数据库、缓存、队列、定时任务等能力全部通过独立插件按需引入。

| 插件 | composer 包名 | 发布命名空间 | 启动入口 | 说明 |
|------|---------------|--------------|----------|------|
| [mon-http](https://github.com/MonGDCH/mon-http) | `mongdch/mon-http` | `mon\http` | `bin/http.php` | HTTP 服务（支持 `Workerman` / `FPM` 双模式）、路由、中间件、Session、依赖注入 |
| [think-orm](https://github.com/MonGDCH/think-orm) | `mongdch/think-orm` | `mon\thinkORM` | - | 数据库 ORM，扩展 `Dao` 对象，支持常驻内存环境 |
| [mon-cache](https://github.com/MonGDCH/mon-cache) | `mongdch/mon-cache` | `mon\cache` | - | 基于 `PSR-16` 的缓存库，内置 `File` / `Redis` 驱动 |
| [gaia-crontab](https://github.com/MonGDCH/gaia-crontab) | `mongdch/gaia-crontab` | `gaia\crontab` | `bin/crontab.php` | 定时任务，支持数组 `Job` 与 `Mysql` 两种任务定义驱动 |
| [gaia-queue](https://github.com/MonGDCH/gaia-queue) | `mongdch/gaia-queue` | `gaia\queue` | `bin/queue.php` | 基于 `Redis` 的消息队列，支持延迟队列、失败重试 |
| [gaia-channel](https://github.com/MonGDCH/gaia-channel) | `mongdch/gaia-channel` | `gaia\channel` | `bin/channel.php` | 跨进程 / 跨服务通信（基于 `workerman/channel`） |
| [gaia-gateway](https://github.com/MonGDCH/gaia-gateway) | `mongdch/gaia-gateway` | `gaia\gateway` | `bin/gateway.php` | WebSocket 服务（基于 `workerman/gateway-worker`） |
| [gaia-nat](https://github.com/MonGDCH/gaia-nat) | `mongdch/gaia-nat` | `gaia\nat` | `bin/nat_server.php` / `bin/nat_client.php` | 内网穿透，服务端 + 客户端双端程序 |

### 插件通用安装流程

```bash
# 1. 安装插件
composer require mongdch/gaia-queue

# 2. 发布插件资源（配置、进程、启动脚本、示例代码）
#    参数为插件的「发布命名空间」，即 composer.json 中 autoload.psr-4 的 key
php gaia vendor:publish gaia\queue

# 3. 按业务修改 config/<插件>/ 下的配置文件（优先走 .env 环境变量）

# 4. 启动插件独立服务（Linux 支持 -d 守护进程模式，Windows 不支持）
php bin/queue.php start
```

> ⚠️ **发布覆盖风险**：`vendor:publish` 采用**强制覆盖**策略（`$overwrite = true`）。
> 插件升级后重新发布会覆盖你已经改过的 `config/`、`support/` 与 `app/` 下的文件，发布前请先备份或纳入版本管理。

### 两种集成方式

| 方式 | 说明 | 适用场景 |
|------|------|----------|
| 独立应用（推荐） | 插件发布 `bin/xxx.php` 启动脚本，内部执行 `App::init('应用名')` 后 `Gaia::instance()->runProcess()`，与主应用 `php gaia app` 完全隔离 | 插件能力独立部署、独立伸缩、独立重启 |
| 合并到主应用 | 将插件发布的进程类注册到 `app/command/AppCommand.php` 的 `$process` 数组，统一由 `php gaia app` 拉起 | 单体部署、进程数量少 |

```php
// app/command/AppCommand.php —— 合并到主应用的方式
protected $process = [
    'http'      => \support\http\process\Http::class,
    'queue'     => \support\queue\process\Queue::class,
    'crontab'   => \support\crontab\process\Server::class,
];
```

---

## 插件文档

### mon-http（HTTP 服务）

基于 `fast-route` 的路由 + 中间件机制的 HTTP 服务，同时支持 `Workerman` 常驻内存模式与 `FPM` 模式。

```bash
composer require mongdch/mon-http
php gaia vendor:publish mon\http
```

发布后生成：

```
bin/http.php               # 独立应用启动入口
public/index.php           # FPM 模式入口
config/http/app.php        # HTTP 主配置
config/http/cors.php       # 跨域配置
config/http/firewall.php   # 防火墙配置
config/http/middleware.php # 中间件配置
config/http/session.php    # Session 配置
config/http/throttle.php   # 限流配置
support/http/process/Http.php  # HTTP 进程
support/http/Route.php     # 路由定义文件
support/http/Controller.php    # 控制器基类
support/http/ErrorHandler.php  # 异常处理器
support/http/middleware/       # 内置中间件(Cors/Firewall/Throttle)
support/http/command/          # route:show / route:cache / route:clear / route:test 指令
```

**核心配置**（`config/http/app.php`）：

```php
return [
    'exception' => \support\http\ErrorHandler::class,
    'fpm'       => ['enable' => env('HTTP_FPM_ENABLE', false), /* 路由缓存文件 */],
    'workerman' => [
        'config' => [
            'listen'    => 'http://' . env('HTTP_LISTEN_HOST', '0.0.0.0') . ':' . env('HTTP_LISTEN_PORT', 8087),
            'count'     => env('HTTP_WORKER_COUNT', 0),  // 0 = CPU核心数 x 2
            'reusePort' => false,
        ],
        'newCtrl'   => true,                             // 每次请求重新创建控制器
        'static'    => [
            'enable'    => env('HTTP_STATIC_ENABLE', false),
            'path'      => ROOT_PATH . '/public',
            'ext_type'  => [],
        ],
    ],
];
```

**定义路由**（`support/http/Route.php`）：

```php
use mon\http\Route;
use mon\http\Router;

// 闭包路由
Route::instance()->get('/[{name}]', function ($name = 'Gaia HTTP') {
    return "Hello {$name}!";
});

// 控制器路由
Route::instance()->get('/user', [\app\controller\UserController::class, 'index']);

// 路由组 + 中间件 + 命名空间
Route::instance()->group(['path' => '/api', 'middleware' => \support\http\middleware\CorsMiddleware::class], function ($route) {
    $route->post('/login', 'User@login');   // 配合 namespace 使用
});

// 多请求方式
Route::instance()->map(['GET', 'POST'], '/search', function (\mon\http\Request $request) {
    return ['code' => 1, 'dataType' => 'json'];
});

// 命名路由，可通过 route('test') 获取路径
Route::instance()->get('/test', [\app\controller\Index::class, 'test'])->name('test');

// 静态文件路由
Route::instance()->file('/static', ROOT_PATH . '/public/static');
```

**请求 / 响应**：

```php
// 全局助手函数：request() / response() / redirect() / route() / dump()
$request = request();
$request->get('id', 0);          // query 参数
$request->post('name');          // post 参数
$request->json('key');           // json 参数
$request->header('token');
$request->file('avatar');        // 上传文件
$request->ip();
$request->method();
$request->isAjax();

// 响应
return response(200, [], 'ok');
return ['code' => 1, 'msg' => 'ok'];    // 数组自动转 JSON
$response = response();
$response->download($file, 'a.txt');
$response->file($file, $request);
redirect('https://gdmon.com', 302);
```

**自定义中间件**（实现 `mon\http\interfaces\MiddlewareInterface`）：

```php
namespace app\middleware;

use Closure;
use mon\http\interfaces\MiddlewareInterface;
use mon\http\interfaces\RequestInterface;
use mon\http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, Closure $next): Response
    {
        // 前置逻辑
        $response = $next($request);
        // 后置逻辑
        return $response;
    }
}
```

注册方式：`config/http/middleware.php` 的 `''`（全局）/ `fpm` / `workerman` / `static_file` 四个分组，
或在路由定义中通过 `['path' => '/x', 'middleware' => AuthMiddleware::class]` 单独挂载。

**启动**：

```bash
php bin/http.php start        # 前台
php bin/http.php start -d     # 守护进程(Linux)
php bin/http.php reload       # 平滑重载
```

---

### think-orm（数据库）

封装 `topthink/think-orm`，提供 `Dao` 对象简化 CRUD，内置验证器、批量更新/批量增量、修改器/获取器，并针对常驻内存环境做了连接保活与事务兜底。

```bash
composer require mongdch/think-orm
php gaia vendor:publish mon\thinkORM
```

发布后生成 `config/database.php`，关键配置：

```php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type'            => env('DB_TYPE', 'mysql'),
            'hostname'        => env('DB_HOST', '127.0.0.1'),
            'database'        => env('DB_NAME', ''),
            'username'        => env('DB_USER', ''),
            'password'        => env('DB_PASSWORD', ''),
            'hostport'        => env('DB_PORT', 3306),
            'charset'         => 'utf8mb4',
            'prefix'          => '',
            'break_reconnect' => true,     // 断线重连
            'fields_cache'    => false,
            'trigger_sql'     => false,    // 开启需自行注入日志对象，否则常驻进程会爆内存
            'query'           => \mon\thinkORM\extend\Query::class,
        ],
    ],
];
```

**注册 ORM**（常驻内存进程必须调用，第二个参数 `true` 开启长连接心跳保活）：

```php
use mon\thinkORM\ORM;

// 在进程 onWorkerStart 中注册，$longLink=true 会注册 Timer 定时 SELECT 1 保活
ORM::register(true);
```

**Dao 对象**：

```php
namespace app\dao;

use mon\thinkORM\Dao;

class UserDao extends Dao
{
    protected $table = 'user';
    protected $connection = '';
    protected $allow = ['name', 'mobile', 'status'];   // 只允许操作的字段
}
```

```php
$dao = new \app\dao\UserDao();

$dao->get();                                  // 查询单条
$dao->all();                                  // 查询多条
$dao->where('status', 1)->select();           // 链式查询(兼容 think-orm 全部链式方法)
$dao->save(['name' => 'mon']);                // 新增/更新
$dao->saveAll($list);                         // 批量写入
$dao->allowField(['name'])->save($data);      // 限制字段
$dao->db()->where('id', 1)->find();           // 原生查询对象
$dao->query('select * from user where id = ?', [1]);   // 原生SQL
$dao->getError();                             // 获取错误信息
```

**常驻内存注意事项**：
- `ORM::register(true)` 会注册 `Timer` 心跳，请遵守框架的 `Timer` 清理红线；
- `trigger_sql` 默认关闭，开启后必须自行清理 SQL 日志，否则内存持续增长；
- 配合 `mon-http` 使用时注册 `mon\thinkORM\ORMMiddleware` 中间件，可自动清理 SQL 日志并回滚未提交事务。

---

### mon-cache（缓存）

基于 `PSR-16` 的缓存库，内置 `File` / `Redis` 驱动，支持自定义驱动扩展。发布后额外提供 Gaia 专用的 `CacheService`、
`Rdb`（复用框架 Redis 实例）与 `Sqlite`（依赖 think-orm）驱动。

```bash
composer require mongdch/mon-cache
php gaia vendor:publish mon\cache
```

发布后生成：

```
config/cache.php                    # 缓存配置
support/cache/CacheService.php      # 缓存服务(单例 + 断线自愈)
support/cache/extend/Rdb.php        # 复用框架 Redis 实例的驱动
support/cache/extend/Sqlite.php     # Sqlite 缓存驱动
```

**使用**：

```php
use support\cache\CacheService;

$cache = CacheService::instance();

$cache->set('key', 'value', 3600);
$cache->get('key', 'default');
$cache->has('key');
$cache->delete('key');
$cache->clear();
$cache->setMultiple(['a' => 1, 'b' => 2]);
$cache->getMultiple(['a', 'b'], 'default');
$cache->deleteMultiple(['a', 'b']);
$cache->pull('key');                       // 读取并删除
$cache->inc('counter', 1);                 // 自增
$cache->dec('counter', 1);                 // 自减
$cache->store('redis');                    // 切换驱动
```

`CacheService` 会自动按 `keep_alive.ping` 间隔 Ping 服务，失败超过 `keep_alive.reset_max` 次后停止保活并触发
`cache_error` 事件（可通过 `mon\util\Event` 监听）。

---

### gaia-crontab（定时任务）

```bash
composer require mongdch/gaia-crontab
php gaia vendor:publish gaia\crontab
php bin/crontab.php start -d
```

发布后生成：

```
bin/crontab.php                # 启动入口(server + task 两个进程)
config/crontab/app.php         # 主配置(驱动/进程/日志)
config/crontab/job.php         # 数组方式定义的任务列表
support/crontab/CrontabService.php    # 客户端服务(查询运行中任务/重载任务)
support/crontab/process/Server.php    # 调度进程
support/crontab/process/Task.php      # 执行进程
support/crontab/command/              # crontab:make-task / crontab:dbinit 指令
app/crontab/DemoTask.php              # 任务示例
```

**生成任务类**：

```bash
php gaia crontab:make-task CleanLog
```

```php
namespace app\crontab;

class CleanLogTask
{
    public function handler()
    {
        // 业务逻辑（必须 try-catch，异常不要外抛到调度进程）
        return 'CleanLog Task runing';
    }
}
```

**数组方式定义任务**（`config/crontab/job.php`，`rule` 为 6 位表达式：`秒 分 时 日 月 周`）：

```php
return [
    [
        'title'     => '清理日志',
        'type'      => 0,                       // 0-对象方法 1-HTTP请求
        'rule'      => '0/10 * * * * *',        // 每10秒执行
        'target'    => \app\crontab\CleanLogTask::class,   // 支持 CleanLogTask@handler 指定方法
        'params'    => [],
        'singleton' => 0,                       // 0-多次 1-单次
        'status'    => 1,                       // 0-禁用 1-启用
    ],
];
```

**Mysql 方式**：修改 `config/crontab/app.php` 的 `driver` 为 `\gaia\crontab\driver\Mysql::class`，
执行 `php gaia crontab:dbinit` 建表（`crontab` / `crontab_log`），任务即可通过数据库动态管理。

**运行时管理**：

```php
use support\crontab\CrontabService;

CrontabService::getPool();        // 获取当前运行中的任务池
CrontabService::reload([1, 2]);   // 重载指定任务ID
```

---

### gaia-queue（消息队列）

基于 `Redis` 的消息队列，支持延迟队列、失败重试、多队列连接。

```bash
composer require mongdch/gaia-queue
php gaia vendor:publish gaia\queue
php bin/queue.php start -d
```

发布后生成：

```
bin/queue.php                 # 启动入口
config/queue/app.php          # 主配置(消费者目录/回调驱动/进程/日志)
config/queue/queue.php        # Redis 队列连接配置
support/queue/QueueService.php    # 客户端服务(投递/查询)
support/queue/process/Queue.php   # 消费进程
support/queue/command/            # queue:make-queue / queue:dbinit 指令
app/queue/DemoQueue.php           # 消费者示例
```

**队列连接配置**（`config/queue/queue.php`）：

```php
return [
    'default'     => 'default',
    'connections' => [
        'default' => [
            'scheme'        => 'redis',
            'host'          => env('QUEUE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port'          => env('QUEUE_PORT', env('REDIS_PORT', 6379)),
            'auth'          => env('QUEUE_AUTH', env('REDIS_AUTH', '')),
            'database'      => env('QUEUE_DB', 5),
            'max_attempts'  => env('QUEUE_ATTEMPTS', 5),   // 失败重试次数
            'retry_seconds' => env('QUEUE_RETRY', 5),      // 重试间隔(秒)
        ],
    ],
];
```

**生成消费者**：

```bash
php gaia queue:make-queue Order
```

```php
namespace app\queue;

use Throwable;
use mon\util\Instance;
use support\queue\QueueService;
use gaia\queue\ConsumerInterface;

class OrderQueue implements ConsumerInterface
{
    use Instance;

    public function connection(): string { return 'default'; }  // 使用的连接配置
    public function queue(): string      { return 'order-queue'; }
    public function describe(): string   { return '订单队列'; }

    /** 消费逻辑，必须自行 try-catch */
    public function consume($data)
    {
        // 业务处理
        return true;
    }

    /** 便捷投递方法（可选） */
    public function sendQuery(array $data = [], int $delay = 0): bool
    {
        return QueueService::syncSend($this->queue(), $data, $delay, $this->connection());
    }

    /** 失败回调（可选），可返回修改后的 $package */
    public function onConsumeFailure(Throwable $e, array $package) {}

    /** 成功回调（可选） */
    public function onConsumeSuccess($result, array $package) {}
}
```

> `app/queue/` 下的消费者会被消费进程**自动扫描加载**，无需手动注册；同一「连接 + 队列名」不可重复。

**投递消息**：

```php
use support\queue\QueueService;

// 同步投递（FPM / 任意环境可用，直接写 Redis）
QueueService::syncSend('order-queue', ['order_id' => 1], 0, 'default');

// 异步投递（需要 Workerman 环境，经由队列进程转发）
QueueService::asyncSend('order-queue', ['order_id' => 1], 10);

// 查看运行中的队列
QueueService::getPool();
```

---

### gaia-channel（跨进程通信）

封装 `workerman/channel`，提供事件广播（`publish/on`）与事件队列（`enqueue/watch`）两种通信模型。

```bash
composer require mongdch/gaia-channel
php gaia vendor:publish gaia\channel
php bin/channel.php start -d        # 默认监听 frame://0.0.0.0:2206，进程数必须为 1
```

发布后生成：`bin/channel.php`、`support/channel/ChannelService.php`、`support/channel/process/Channel.php`、
`support/channel/command/ChannelCommand.php`。可通过 `php gaia make:channel <名称>` 生成监听进程模板
（`support/process/channel/<Name>.php`），生成后需注册到 `AppCommand::$process`。

**使用**：

```php
use support\channel\ChannelService;

$channel = ChannelService::instance();

// 广播模型：所有监听者都会收到
$channel->on('order.paid', function (array $data) {
    // 处理事件
});
$channel->publish('order.paid', ['order_id' => 1]);
$channel->unsubscribe('order.paid');

// 队列模型：只有一个监听者会收到
$channel->watch('order.paid', function (array $data) {});
$channel->enqueue('order.paid', ['order_id' => 1]);
$channel->unwatch('order.paid');
```

> `ChannelService::instance()` 内部会自动连接 Channel 服务端（地址取自 `support\channel\process\Channel::getListenHost()` /
> `getListenPort()`），**必须在 `bin/channel.php` 服务已启动后使用**。

---

### gaia-gateway（WebSocket）

封装 `workerman/gateway-worker`，发布即包含 `Register` / `BusinessWorker` / `Gateway` 三个进程。

```bash
composer require mongdch/gaia-gateway
php gaia vendor:publish gaia\gateway
php bin/gateway.php start -d
```

发布后生成：

```
bin/gateway.php
config/gateway/app.php        # 日志配置
config/gateway/gateway.php    # Gateway 进程(websocket 端口/心跳/内网IP/起始端口)
config/gateway/business.php   # BusinessWorker 进程(事件处理器 app\gateway\Event)
config/gateway/register.php   # Register 进程(注册中心地址)
support/gateway/GatewayService.php       # Gateway 客户端封装
support/gateway/process/{Register,Business,Gateway}.php
app/gateway/Event.php         # 事件回调
```

**事件回调**（`app/gateway/Event.php`）：

```php
namespace app\gateway;

use GatewayWorker\BusinessWorker;
use support\gateway\GatewayService;

class Event
{
    public static function onWorkerStart(BusinessWorker $worker)
    {
        // 日志通道、ORM::register(true) 等初始化
    }

    public static function onConnect(string $client_id)
    {
        GatewayService::instance()->sendToCurrentClient('Hello！' . $client_id);
    }

    public static function onWebSocketConnect(string $client_id, array $data) {}

    public static function onMessage(string $client_id, string $message)
    {
        GatewayService::instance()->sendToCurrentClient('Your Message => ' . $message);
    }

    public static function onClose(string $client_id) {}
}
```

**常用 API**（`support\gateway\GatewayService`，全部透传 `GatewayWorker\Lib\Gateway`）：

```php
$gateway = GatewayService::instance();

$gateway->sendToCurrentClient($msg);
$gateway->sendToClient($client_id, $msg);
$gateway->sendToUid($uid, $msg);
$gateway->sendToGroup($group, $msg);
$gateway->sendToAll($msg);
$gateway->bindUid($client_id, $uid);
$gateway->unbindUid($client_id, $uid);
$gateway->joinGroup($client_id, $group);
$gateway->leaveGroup($client_id, $group);
$gateway->isOnline($client_id);
$gateway->isUidOnline($uid);
$gateway->getClientIdByUid($uid);
$gateway->getAllClientIdList();
$gateway->closeClient($client_id);
$gateway->setSession($client_id, ['uid' => $uid]);
$gateway->getSession($client_id);
```

> 客户端连接地址即 `config/gateway/gateway.php` 中的 `listen`（默认 `websocket://0.0.0.0:12301`）。

---

### gaia-nat（内网穿透）

服务端（`bin/nat_server.php`）+ 客户端（`bin/nat_client.php`）双端程序，把本地服务暴露到外网。

```bash
composer require mongdch/gaia-nat
php gaia vendor:publish gaia\nat
```

发布后生成：`bin/nat_server.php`、`bin/nat_client.php`、`support/nat/process/`、`config/nat.php`。
**注意**：发布时会自动生成随机 `token`（写入 `config/nat.php`），服务端与客户端必须使用**同一份 token**。

**配置**（`config/nat.php`）：

```php
return [
    'debug'         => env('NAT_DEBUG', false),
    'token'         => env('NAT_TOKEN', '<发布时自动生成的随机串>'),
    'channel_host'  => env('NAT_CHANNEL_HOST', 'nat.gdmon.com'),   // 服务端通信地址
    'channel_port'  => env('NAT_CHANNEL_PORT', 2209),
    'proxy_host'    => env('NAT_PROXY_HOST', '127.0.0.1'),         // 客户端：本地被穿透的服务
    'proxy_port'    => env('NAT_PROXY_PORT', 80),
    'server'        => [
        'listen'    => 'tcp://127.0.0.1:' . env('NAT_SERVER_PORT', 8087),  // 服务端对外监听
        'count'     => env('NAT_SERVER_COUNT', 1),
    ],
];
```

**启动**：

```bash
# 服务端（公网机器）
php bin/nat_server.php start -d

# 客户端（内网机器）
php bin/nat_client.php start -d
```

服务端监听为 `tcp` 协议，支持 `http` / `https` / `tcp` 三种穿透方式；可配合 `nginx` 反向代理实现域名访问与端口隐藏：

```conf:nginx
upstream gaia-nat {
    server 127.0.0.1:8087;
    keepalive 10240;
}

server {
  server_name dev.test;
  listen 80;
  location / {
    try_files $uri $uri/ @proxy;
  }
  location @proxy {
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_http_version 1.1;
    proxy_set_header Connection "";
    proxy_pass http://gaia-nat;
  }
}
```

---

## AI 开发技能

项目内置了 Gaia 框架的 AI 开发技能，位于 `skills/gaia/`：

| 文件 | 说明 |
|------|------|
| `skills/gaia/SKILL.md` | 主技能：强制编码规范、进程/指令/服务骨架、常驻内存红线、插件能力索引 |
| `skills/gaia/references/code-development-spec.md` | 完整开发规范 |
| `skills/gaia/references/plugins.md` | 插件详细用法参考（安装/发布/配置/API/常驻内存注意事项） |
| `skills/gaia/assets/` | 代码模板（Service / Process / Command / Config 及插件模板） |
| `skills/gaia/scripts/lint.php` | 代码规范检查脚本 |

在支持 Agent Skills 的编辑器中打开本目录，AI 会自动加载 `gaia` 技能，可根据需求直接生成符合规范的进程、
指令、Service 以及各插件的业务代码。
