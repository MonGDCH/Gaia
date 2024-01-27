<?php

declare(strict_types=1);

namespace support\service;

use Redis;
use Throwable;
use mon\env\Config;
use Workerman\Timer;
use RuntimeException;
use mon\util\Instance;
use BadFunctionCallException;
use InvalidArgumentException;

/**
 * Redis操作类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 2018-05-20
 * @version 1.1.0 2019-12-02 修复自定义Redis配置无效的BUG
 * @version 1.2.0 2021-03-29 优化代码，增强注解
 * @version 1.2.1 2023-05-10 增加连接池功能，增强注解
 * @version 1.2.2 2023-05-12 增加连接保活功能
 */
class RedisService
{
    use Instance;

    /**
     * Redis连接池
     *
     * @var array
     */
    protected $pool = [];

    /**
     * 保活连接池
     *
     * @var array
     */
    protected $keeps = [];

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 链接host
        'host'      => '127.0.0.1',
        // 链接端口
        'port'      => 6379,
        // 链接密码
        'auth'      => '',
        // 自定义键前缀
        'prefix'    => '',
        // redis数据库
        'database'  => 1,
        // 读取超时时间
        'timeout'   => 2,
        // 连接保活，0则不保活
        'ping'      => 0,
        // 保持链接
        'persistent' => false,
    ];

    /**
     * 最大重启次数
     *
     * @var integer
     */
    protected $err_max = 5;

    /**
     * 当前重启次数
     *
     * @var integer
     */
    protected $err_count = 0;

    /**
     * 构造方法
     */
    protected function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('redis', []));
    }

    /**
     * 获取Redis实例
     *
     * @param array $config     配置信息
     * @param boolean $reset    是否重置连接
     * @return Redis
     */
    public function getRedis(array $config = [], bool $reset = false): Redis
    {
        if (!extension_loaded('redis')) {
            throw new BadFunctionCallException('Not support Redis');
        }

        $config = array_merge($this->config, $config);
        $key = $this->getKey($config);
        if (!isset($this->pool[$key]) || $reset) {
            // 移除保活处理机制
            if (isset($this->keeps[$key])) {
                Timer::del($this->keeps[$key]);
                unset($this->keeps[$key]);
            }
            $this->pool[$key] = new Redis();
            if ($this->config['persistent']) {
                // 持久连接
                $this->pool[$key]->pconnect($config['host'], $config['port'], $config['timeout'], 'persistent_id_' . $config['database']);
            } else {
                // 短连接
                $this->pool[$key]->connect($config['host'], $config['port'], $config['timeout']);
            }
            // 密码
            if ($config['auth']) {
                $this->pool[$key]->auth($config['auth']);
            }
            // 选择库
            if ($config['database']) {
                $this->pool[$key]->select($config['database']);
            }
            // 设置前缀
            if ($config['prefix']) {
                $this->pool[$key]->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }
            // 设置超时时间
            if ($config['timeout']) {
                $this->pool[$key]->setOption(Redis::OPT_READ_TIMEOUT, $config['timeout']);
            }

            // 是否启用保活机制
            if ($config['ping'] > 0 && !defined('IN_FPM') && class_exists(Timer::class)) {
                $this->keep($key, $config['ping']);
            }
        }

        return $this->pool[$key];
    }

    /**
     * 执行redis指令，链接失败或超时自动重连
     *
     * @param array $config redis配置
     * @param string $command   执行的指令
     * @param mixed ...$options 指令参数
     * @return mixed
     */
    public function tryExecCommand(array $config, string $command, ...$options)
    {
        try {
            $redis = $this->getRedis($config);
            $result = $redis->{$command}(...$options);
            // 连接正常，清空错误计数
            $this->err_count = 0;
            return $result;
        } catch (Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (($this->err_max >= $this->err_count) && ($msg === 'connection lost' || strpos($msg, 'went away'))) {
                $this->err_count++;
                $this->getRedis($config, true);
                return $this->tryExecCommand($config, $command, ...$options);
            }

            throw $e;
        }
    }

    /**
     * 获取配置Key值
     *
     * @param  array  $config 配置信息
     * @return string
     */
    public function getKey(array $config): string
    {
        return md5(serialize($config));
    }

    /**
     * 连接保活
     *
     * @param string $key   连接池key名
     * @param integer $ping ping的间隔时间
     * @return integer  定时器ID
     */
    protected function keep(string $key, int $ping): int
    {
        if (isset($this->keeps[$key])) {
            return $this->keeps[$key];
        }

        $redis = $this->pool[$key];
        $this->keeps[$key] = Timer::add($ping, function (Redis $redis) {
            $redis->ping();
        }, [$redis]);
        return $this->keeps[$key];
    }

    /************* string类型操作命令 *****************/

    /**
     * 获取key值
     *
     * @param  string $key 键名
     * @return string|false
     */
    public function get(string $key)
    {
        return $this->getRedis()->get($key);
    }

    /**
     * 获取多个key值
     *
     * @param  array  $keys 键名
     * @return array|false
     */
    public function mGet(array $keys)
    {
        return $this->getRedis()->mGet($keys);
    }

    /**
     * 返回字符串的一部分
     *
     * @param  string $key   key名
     * @param  integer $start 起始点
     * @param  integer $end   结束点
     * @return string|false
     */
    public function getRange(string $key, $start, $end)
    {
        return $this->getRedis()->getRange($key, $start, $end);
    }

    /**
     * 返回字符串长度
     *
     * @param  string $key 键名
     * @return integer
     */
    public function strlen(string $key): int
    {
        return $this->getRedis()->strlen($key);
    }

    /**
     * 获取key的原值，并设置新值，不存在原值则返回false
     *
     * @param  string $key   键名
     * @param  string $value 键值
     * @return string
     */
    public function getSet(string $key, $value)
    {
        return $this->getRedis()->getSet($key, $value);
    }

    /**
     * 设置一个key值
     *
     * @param string $key     键名
     * @param string $value   键名
     * @param mixed  $options 其他参数
     * @return boolean
     */
    public function set($key, $value, $options = null)
    {
        return $this->getRedis()->set($key, $value, $options);
    }

    /**
     * 添加指定的字符串到指定的字符串key
     *
     * @param  string $key   键名
     * @param  string $value 键值
     * @return integer 返回新的key size
     */
    public function append($key, $value)
    {
        return $this->getRedis()->append($key, $value);
    }

    /**
     * 设置一个有过期时间的key值, 单位秒
     * 
     * @param  string  $key    键名
     * @param  integer $expire 有效时间, 单位秒
     * @param  string  $value  键值
     * @return boolean
     */
    public function setex($key, $expire, $value)
    {
        return $this->getRedis()->setex($key, $expire, $value);
    }

    /**
     * 设置一个有过期时间的key值, 单位毫秒
     * 
     * @param  string  $key    键
     * @param  integer $expire 有效时间，单位毫秒
     * @param  string  $value  值
     * @return boolean
     */
    public function psetex($key, $expire, $value)
    {
        return $this->getRedis()->psetex($key, $expire, $value);
    }

    /**
     * 设置一个key值,如果key存在,不做任何操作
     * 
     * @param  string $key   键
     * @param  string $value 值
     * @return boolean
     */
    public function setnx($key, $value)
    {
        return $this->getRedis()->setnx($key, $value);
    }

    /**
     * 替换字符串的一部分, 主要配置setex, 实现更新值有效时间不更新
     *
     * @param string  $key    key
     * @param integer $offset 偏移值
     * @param string  $value  值
     * @return string 修改后的字符串长度
     */
    public function setRange($key, $offset, $value)
    {
        return $this->getRedis()->setRange($key, $offset, $value);
    }

    /**
     * 批量设置key值
     * 
     * @param  array $array 键值对
     * @return boolean
     */
    public function mSet($array)
    {
        return $this->getRedis()->mSet($array);
    }

    /**
     * 移除已经存在key
     *
     * @param  string|array $key key名，字符串或者数组
     * @return integer 返回删除KEY-VALUE的数量
     */
    public function delete($key)
    {
        return $this->getRedis()->del($key);
    }

    /**
     * 判断一个key值是不是存在
     *
     * @param string $key 键名
     * @return boolean
     */
    public function exists($key)
    {
        return $this->getRedis()->exists($key);
    }

    /**
     * 对key的值加value, 相当于 key = keyValue + value
     *
     * @param  string  $key   键
     * @param  integer $value 值
     * @return integer 返回新的INT数值
     */
    public function incrBy($key, $value)
    {
        return $this->getRedis()->incrBy($key, $value);
    }

    /**
     * 对key的值加value, 相当于 key = keyValue + value
     *
     * @param  string $key   键
     * @param  float  $value 值
     * @return float
     */
    public function incrByFloat($key, $value)
    {
        return $this->getRedis()->incrByFloat($key, $value);
    }

    /**
     * 对key的值减value, 相当于 key = keyValue - value
     *
     * @param  string  $key   键
     * @param  integer $value 值
     * @return integer 返回新的INT数值
     */
    public function decrBy($key, $value)
    {
        return $this->getRedis()->decrBy($key, $value);
    }

    /**
     * 对key的值减value, 相当于 key = keyValue - value
     *
     * @param  string $key   键
     * @param  float  $value 值
     * @return float 返回新的float数值
     */
    public function decrByFloat($key, $value)
    {
        return $this->getRedis()->incrByFloat($key, (0 - $value));
    }

    /***************** hash类型操作函数 *******************/

    /**
     * 为hash表设定一个字段的值
     * 
     * @param  string $key   键
     * @param  string $field 字段
     * @param  string $value 值
     * @return string|false
     */
    public function hSet($key, $field, $value)
    {
        return $this->getRedis()->hSet($key, $field, $value);
    }

    /**
     * 得到hash表中一个字段的值
     * 
     * @param  string $key   键
     * @param  string $field 字段
     * @return string|false
     */
    public function hGet($key, $field)
    {
        return $this->getRedis()->hGet($key, $field);
    }

    /**
     * 删除hash表中指定字段 ,支持批量删除
     *
     * @param  string $key   键
     * @param  string $field 字段
     * @return boolean
     */
    public function hDel($key, $field)
    {
        $delNum = 0;
        if (is_array($field)) {
            // 字符串，批量删除
            foreach ($field as $row) {
                $delNum += $this->getRedis()->hDel($key, $row);
            }
        } else {
            // 字符串，删除单个
            $delNum += $this->getRedis()->hDel($key, $field);
        }

        return $delNum;
    }

    /**
     * 返回hash表元素个数
     *
     * @param  string $key 键
     * @return integer|false
     */
    public function hLen($key)
    {
        return $this->getRedis()->hLen($key);
    }

    /**
     * 为hash表设定一个字段的值,如果字段存在，返回false
     *
     * @param  string $key   键
     * @param  string $field 字段
     * @param  string $value 值
     * @return boolean
     */
    public function hSetNx($key, $field, $value)
    {
        return $this->getRedis()->hSetNx($key, $field, $value);
    }

    /**
     * 为hash表多个字段设定值。
     * 
     * @param  string $key  键
     * @param  array $value 值(字段 => 值)
     * @return boolean
     */
    public function hMset($key, array $value)
    {
        if (!is_array($value)) {
            return false;
        }

        return $this->getRedis()->hMset($key, $value);
    }

    /**
     * 获取hash表多个字段值。
     *
     * @param string $key  键
     * @param array|string $value 值，string则以','号分隔字段
     * @return array|bool
     */
    public function hMget($key, $field)
    {
        if (!is_array($field)) {
            $field = explode(',', $field);
        }

        return $this->getRedis()->hMget($key, $field);
    }

    /**
     * 为hash表的某个值累加整数，可以负数
     * 
     * @param string $key    key值
     * @param string $field  字段
     * @param integer $value 步长
     * @return integer
     */
    public function hIncrBy($key, $field, $value)
    {
        $value = intval($value);

        return $this->getRedis()->hIncrBy($key, $field, $value);
    }

    /**
     * 为hash表的某个值累加浮点数，可以负数
     * 
     * @param string $key   key值
     * @param string $field 字段
     * @param float  $value 步长
     * @return float
     */
    public function hIncrByFloat($key, $field, $value)
    {
        $value = floatval($value);

        return $this->getRedis()->hIncrByFloat($key, $field, $value);
    }

    /**
     * 返回所有hash表的所有字段
     *
     * @param string $key 键
     * @return array
     */
    public function hKeys($key)
    {
        return $this->getRedis()->hKeys($key);
    }

    /**
     * 返回所有hash表的字段值，为一个索引数组
     * 
     * @param string $key 键
     * @return array
     */
    public function hVals($key)
    {
        return $this->getRedis()->hVals($key);
    }

    /**
     * 验证HASH表中是否存在指定的key-hashKey
     *
     * @param  string $key   键
     * @param  string $hashKey 值
     * @return boolean
     */
    public function hExists($key, $hashKey)
    {
        return $this->getRedis()->hExists($key, $hashKey);
    }

    /**
     * 返回所有hash表的字段值，为一个关联数组
     * 
     * @param string $key 键
     * @return array
     */
    public function hGetAll($key)
    {
        return $this->getRedis()->hGetAll($key);
    }

    /********************* List队列类型操作命令 ************************/

    /**
     * 在队列尾部插入一个元素
     * 
     * @param string $key 键
     * @param string $value 值
     * @return integer|false 返回队列长度
     */
    public function rPush(string $key, $value)
    {
        return $this->getRedis()->rPush($key, $value);
    }

    /**
     * 批量在队列尾部插入元素
     *
     * @param string $key   键
     * @param array $values 值，[0, 1, 2, 3, 4]
     * @return integer|false 返回队列长度
     */
    public function batch_rPush(string $key, array $values)
    {
        return call_user_func_array([$this->getRedis(), 'rPush'], [$key, ...$values]);
    }

    /**
     * 在队列尾部插入一个元素 如果key不存在，什么也不做
     * 
     * @param string $key 键
     * @param string $value 值
     * @return integer|false 返回队列长度
     */
    public function rPushx(string $key, $value)
    {
        return $this->getRedis()->rPushx($key, $value);
    }

    /**
     * 在队列头部插入一个元素
     * 
     * @param string $key 键
     * @param string $value 值
     * @return integer|false 返回队列长度
     */
    public function lPush(string $key, $value)
    {
        return $this->getRedis()->lPush($key, $value);
    }

    /**
     * 批量在队列头部插入元素
     *
     * @param string $key   键
     * @param array $values 值，[0, 1, 2, 3, 4]
     * @return integer|false 返回队列长度
     */
    public function batch_lPush(string $key, array $values)
    {
        return call_user_func_array([$this->getRedis(), 'lPush'], [$key, ...$values]);
    }

    /**
     * 在队列头插入一个元素 如果key不存在，什么也不做
     *
     * @param string $key 键
     * @param string $value 值
     * @return integer|false 返回队列长度
     */
    public function lPushx(string $key, $value)
    {
        return $this->getRedis()->lPushx($key, $value);
    }

    /**
     * 删除并返回队列中的头元素。
     * 
     * @param string $key   键
     * @return string|false
     */
    public function lPop(string $key)
    {
        return $this->getRedis()->lPop($key);
    }

    /**
     * 批量lpop
     *
     * @param string $key   键
     * @param integer $num  pop的数量
     * @return array
     */
    public function batch_lpop(string $key, int $num): array
    {
        $pipe = $this->multi(Redis::PIPELINE);
        $pipe->lRange($key, 0, $num - 1);
        $pipe->lTrim($key, $num, -1);
        $result = $pipe->exec();
        if (!$result[1]) {
            // 删除失败
            throw new RuntimeException('Remove batch pop keys faild.');
        }
        return $result[0];
    }

    /**
     * 删除并返回队列中的尾元素
     * 
     * @param string $key   键
     * @return string|false
     */
    public function rPop(string $key)
    {
        return $this->getRedis()->rPop($key);
    }

    /**
     * 删除并或取列表的第一个元素，如果没有元素则会阻塞直到等待超时
     *
     * @param string $key   键
     * @param integer $timeout  超时时间
     * @return string|false
     */
    public function blPop(string $key, int $timeout = 3)
    {
        return $this->getRedis()->blPop($key, $timeout);
    }

    /**
     * 删除并或取列表的最后一个元素，如果没有元素则会阻塞直到等待超时
     *
     * @param string $key   键
     * @param integer $timeout  超时时间
     * @return string|false
     */
    public function brPop(string $key, int $timeout = 3)
    {
        return $this->getRedis()->brPop($key, $timeout);
    }

    /**
     * 返回队列长度
     * 
     * @param string $key 值
     * @return integer
     */
    public function lLen(string $key)
    {
        return $this->getRedis()->lLen($key);
    }

    /**
     * 返回队列指定区间的元素
     * 
     * @param string $key    键
     * @param integer $start 起点
     * @param integer $end   终点
     * @return array
     */
    public function lRange($key, $start, $end)
    {
        return $this->getRedis()->lRange($key, $start, $end);
    }

    /**
     * 截取LIST中指定范围内的元素组成一个新的LIST并指向KEY
     *
     * @param  string $key    键
     * @param  integer $start 起点
     * @param  integer $end   终点
     * @return array
     */
    public function lTrim($key, $start, $end)
    {
        return $this->getRedis()->lTrim($key, $start, $end);
    }

    /**
     * 返回队列中指定索引的元素
     * 
     * @param string $key   键
     * @param integer $index 值
     * @return mixed
     */
    public function lIndex($key, $index)
    {
        return $this->getRedis()->lIndex($key, $index);
    }

    /**
     * 根据索引值返回指定KEY-LIST中的元素，0为第一个
     *
     * @param  string  $key   键
     * @param  integer $index 值
     * @return string|false
     */
    public function lGet($key, $index)
    {
        return $this->getRedis()->lGet($key, $index);
    }

    /**
     * 设定队列中指定index的值。
     * 
     * @param string  $key   键
     * @param integer $index 索引
     * @param string  $value 值
     * @return boolean
     */
    public function lSet($key, $index, $value)
    {
        return $this->getRedis()->lSet($key, $index, $value);
    }

    /**
     * 删除值为vaule的count个元素
     * PHP-redis扩展的数据顺序与命令的顺序不太一样，不知道是不是bug
     * count>0 从尾部开始
     *  >0　从头部开始
     *  =0　删除全部
     *  
     * @param string $key   键
     * @param integer $count 数量
     * @param string $value 值
     * @return integer|false
     */
    public function lRem($key, $count, $value)
    {
        return $this->getRedis()->lRem($key, $value, $count);
    }

    /**
     * 移除列表key1中第一个元素，将其插入另一个列表asd头部，并返回这个元素。若源列表没有元素则返回false
     *
     * @param  string $key        键
     * @param  string $target_key 目标键
     * @return string|false
     */
    public function rpoplpush($key, $target_key)
    {
        return $this->getRedis()->rpoplpush($key, $target_key);
    }

    /**
     * 移除列表key1中最后一个元素，将其插入另一个列表asd头部，并返回这个元素。如果列表没有元素则会阻塞列表直到超时,超时返回false
     *
     * @param  string  $key         键
     * @param  string  $target_key  目标键
     * @param  integer $timeout     超时时间
     * @return string|false
     */
    public function brpoplpush($key, $target_key, $timeout = 3)
    {
        return $this->getRedis()->brpoplpush($key, $target_key, $timeout);
    }

    /************* 无序集合操作命令 *****************/

    /**
     * 返回集合中所有元素
     *
     * @param string $key   键
     * @return array
     */
    public function sMembers($key)
    {
        return $this->getRedis()->sMembers($key);
    }

    /**
     * 检查VALUE是否是key-SET容器中的成员
     *
     * @param  string $key   键
     * @param  string $value 值
     * @return boolean
     */
    public function sIsMember($key, $value)
    {
        return $this->getRedis()->sIsMember($key, $value);
    }

    /**
     * 添加集合
     *
     * @param string $key   键
     * @param string $value 值
     * @return boolean
     */
    public function sAdd($key, $value)
    {
        return $this->getRedis()->sAdd($key, $value);
    }

    /**
     * 返回无序集合的元素个数
     *
     * @param string $key   键
     * @return integer
     */
    public function sCard($key)
    {
        return $this->getRedis()->sCard($key);
    }

    /**
     * 随机返回一个元素，并且在key-SET容器中移除该元素。
     *
     * @param  string $key 键
     * @return string|false
     */
    public function sPop($key)
    {
        return $this->getRedis()->sPop($key);
    }

    /**
     * 取得指定key-SET容器中的一个随机元素，但不会在key-SET容器中移除它
     *
     * @param  string $key 键
     * @return string|false
     */
    public function sRandMember($key)
    {
        return $this->getRedis()->sRandMember($key);
    }

    /**
     * 从集合中删除一个元素
     *
     * @param string $key   键
     * @param string $value 值
     * @return boolean
     */
    public function sRem($key, $value)
    {
        return $this->getRedis()->sRem($key, $value);
    }

    /**
     * 移动一个指定的MEMBER从key-SET到指定的target-SET中
     *
     * @param  string $key    键
     * @param  string $target 目标键
     * @param  string $member 成员值
     * @return string|false
     */
    public function sMove($key, $target, $member)
    {
        return $this->getRedis()->sMove($key, $target, $member);
    }

    /**
     * 返回指定两个SETS集合的交集结果，注意：原生的sInter可传N个key名
     *
     * @param  string $key1 键1
     * @param  string $key2 键2
     * @return array
     */
    public function sInter($key1, $key2)
    {
        return $this->getRedis()->sInter($key1, $key2);
    }

    /**
     * 执行一个交集操作，并把结果存储到一个新的SET容器中
     *
     * @param  string $name 新的key名
     * @param  string $key1 键1
     * @param  string $key2 键2
     * @return integer
     */
    public function sInterStore($name, $key1, $key2)
    {
        return $this->getRedis()->sInterStore($name, $key1, $key2);
    }

    /**
     * 返回指定两个SETS集合的并集结果，注意：原生的sUnion可传N个key名
     *
     * @param  string $key1 键1
     * @param  string $key2 键2
     * @return array
     */
    public function sUnion($key1, $key2)
    {
        return $this->getRedis()->sUnion($key1, $key2);
    }

    /**
     * 执行一个并集操作，并把结果存储到一个新的SET容器中
     *
     * @param  string $name 新的key名
     * @param  string $key1 键1
     * @param  string $key2 键2
     * @return integer 并集结果的个数
     */
    public function sUnionStore($name, $key1, $key2)
    {
        return $this->getRedis()->sUnionStore($name, $key1, $key2);
    }

    /**
     * 求2个集合的差集
     *
     * @param string $key1  键1
     * @param string $key2  键2
     * @return array
     */
    public function sDiff($key1, $key2)
    {
        return $this->getRedis()->sDiff($key1, $key2);
    }

    /**
     * 执行一个差集操作，并把结果存储到一个新的SET容器中
     *
     * @param  string $name 键名
     * @param  string $key1 键1
     * @param  string $key2 键2
     * @return integer 结果集的个数
     */
    public function sDiffStore($name, $key1, $key2)
    {
        return $this->getRedis()->sDiffStore($name, $key1, $key2);
    }

    /**
     * 筛选集合
     *
     * @param  string  $key    键
     * @param  integer $option 其他信息
     * @return array
     */
    public function sort($key, $option = null)
    {
        return $this->getRedis()->sort($key, $option);
    }

    /********************* sorted set有序集合类型操作命令 *********************/

    /**
     * 给当前集合添加一个元素，如果value已经存在，会更新order的值。
     * 
     * @param string $key   键
     * @param string $order 序号
     * @param string $value 值
     * @return boolean
     */
    public function zAdd($key, $order, $value)
    {
        return $this->getRedis()->zAdd($key, $order, $value);
    }

    /**
     * 从有序集合中删除指定的成员
     *
     * @param  string $key   键
     * @param  string $value 值
     * @return boolean
     */
    public function zDelete($key, $value)
    {
        return $this->getRedis()->zDelete($key, $value);
    }

    /**
     * 删除值为value的元素
     * 
     * @param string $key   键
     * @param string $value 值
     * @return boolean
     */
    public function zRem($key, $value)
    {
        return $this->getRedis()->zRem($key, $value);
    }

    /**
     * 集合以order递增排列后，0表示第一个元素，-1表示最后一个元素
     * 
     * @param string  $key   键
     * @param integer $start 开始位置
     * @param integer $end   结束位置
     * @param boolean $order 是否返回排序值
     * @return array
     */
    public function zRange($key, $start, $end, $order = false)
    {
        return $this->getRedis()->zRange($key, $start, $end, $order);
    }

    /**
     * 集合以order递减排列后，0表示第一个元素，-1表示最后一个元素
     * 
     * @param string  $key   键
     * @param integer $start 开始位置
     * @param integer $end   结束位置
     * @param boolean $order 是否返回排序值
     * @return array
     */
    public function zRevRange($key, $start, $end, $order = false)
    {
        return $this->getRedis()->zRevRange($key, $start, $end, $order);
    }

    /**
     * 集合以order递增排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * 
     * @param string  $key    键
     * @param integer $start  开始位置
     * @param integer $end    结束位置
     * @param array   $option 参数
     *     withscores=>true，表示数组下标为Order值，默认返回索引数组
     *     limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array
     */
    public function zRangeByScore($key, $start = '-inf', $end = "+inf", $option = [])
    {
        return $this->getRedis()->zRangeByScore($key, $start, $end, $option);
    }

    /**
     * 集合以order递减排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * 
     * @param string  $key    键
     * @param integer $start  开始位置
     * @param integer $end    结束位置
     * @param array   $option 参数
     *     withscores=>true，表示数组下标为Order值，默认返回索引数组
     *     limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array
     */
    public function zRevRangeByScore($key, $start = '-inf', $end = "+inf", $option = [])
    {
        return $this->getRedis()->zRevRangeByScore($key, $start, $end, $option);
    }

    /**
     * 返回order值在start end之间的数量
     * 
     * @param string  $key   键
     * @param integer $start 开始位置
     * @param integer $end   结束位置
     * @return integer
     */
    public function zCount($key, $start, $end)
    {
        return $this->getRedis()->zCount($key, $start, $end);
    }

    /**
     * 返回值为value的order值
     * 
     * @param string $key   键
     * @param float  $value 值
     * @return float
     */
    public function zScore($key, $value)
    {
        return $this->getRedis()->zScore($key, $value);
    }

    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * 
     * @param string $key   键
     * @param float  $value 值
     * @return float
     */
    public function zRank($key, $value)
    {
        return $this->getRedis()->zRank($key, $value);
    }

    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * 
     * @param string $key   键
     * @param float  $value 值
     * @return float
     */
    public function zRevRank($key, $value)
    {
        return $this->getRedis()->zRevRank($key, $value);
    }

    /**
     * 删除集合中，score值在start end之间的元素　包括start end
     * min和max可以是-inf和+inf　表示最大值，最小值
     * 
     * @param string  $key   键
     * @param integer $start 开始位置
     * @param integer $end   结束位置
     * @return integer 删除成员的数量。
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->getRedis()->zRemRangeByScore($key, $start, $end);
    }

    /**
     * 删除集合中，score值在start end之间的元素　不包括start end
     *
     * @param string  $key   键
     * @param integer $start 开始位置
     * @param integer $end   结束位置
     * @return integer
     */
    public function zRemRangeByRank($key, $start, $end)
    {
        return $this->getRedis()->zRemRangeByRank($key, $start, $end);
    }

    /**
     * 返回集合元素个数。
     * 
     * @param string $key   键
     * @return integer
     */
    public function zCard($key)
    {
        return $this->getRedis()->zCard($key);
    }

    /**
     * 返回集合元素个数。
     *
     * @param  string $key 键
     * @return integer
     */
    public function zSize($key)
    {
        return $this->getRedis()->zSize($key);
    }

    /**
     * 将key对应的有序集合中member元素的scroe加上increment，increment可以为负数
     * 如果指定的member不存在，那么将会添加该元素，并且其score的初始值为increment。
     * 如果key不存在，那么将会创建一个新的有序列表，其中包含member这一唯一的元素。
     * 如果key对应的值不是有序列表，那么将会发生错误。
     * 指定的score的值应该是能够转换为数字值的字符串，并且接收双精度浮点数。同时，你也可用提供一个负值，这样将减少score的值。
     *
     * @param  string $key    键
     * @param  float  $value  值
     * @param  string $member 成员
     * @return float
     */
    public function zIncrBy($key, $value, $member)
    {
        return $this->getRedis()->zIncrBy($key, $value, $member);
    }

    /********************* 事务的相关方法 ************************/

    /**
     * 监控key,就是一个或多个key添加一个乐观锁
     * 在此期间如果key的值如果发生的改变，刚不能为key设定值
     * 可以重新取得Key的值。
     * 
     * @param array $key   键列表
     * @return mixed
     */
    public function watch(array $key)
    {
        return $this->getRedis()->watch($key);
    }

    /**
     * 取消当前链接对所有key的watch
     *  EXEC 命令或 DISCARD 命令先被执行了的话，那么就不需要再执行 UNWATCH 了
     * 
     * @return mixed
     */
    public function unwatch()
    {
        return $this->getRedis()->unwatch();
    }

    /**
     * 开启一个事务
     * 事务的调用有两种模式Redis::MULTI和Redis::PIPELINE，
     * 默认是Redis::MULTI模式，
     * Redis::PIPELINE管道模式速度更快，但没有任何保证原子性有可能造成数据的丢失
     *
     * @param integer $type  事务启动方式
     * @return Redis
     */
    public function multi($type = Redis::MULTI)
    {
        return $this->getRedis()->multi($type);
    }

    /**
     * 执行一个事务
     * 收到 EXEC 命令后进入事务执行，事务中任意命令执行失败，其余的命令依然被执行
     * 
     * @return mixed
     */
    public function exec()
    {
        return $this->getRedis()->exec();
    }

    /**
     * 回滚一个事务
     * 
     * @return mixed
     */
    public function discard()
    {
        return $this->getRedis()->discard();
    }

    /**
     * 执行Lua脚本
     *
     * @param string $script    执行的脚本
     * @param array $params     入参
     * @param integer $numKeys  key数
     * @return mixed
     */
    public function eval(string $script, array $params = [], int $numKeys = 0)
    {
        return $this->getRedis()->eval($script, $params, $numKeys);
    }

    /************* 订阅操作命令 *****************/

    /**
     * 订阅频道
     *
     * @param  string $key      订阅的频道名，可字符串，可数组
     * @param  mixed  $callback 回调函数，function($redis, $chan, $msg){}
     * @return mixed
     */
    public function subscribe($key, $callback)
    {
        return $this->getRedis()->subscribe($key, $callback);
    }

    /**
     * 发布订阅
     *
     * @param  string $channel 发布的频道
     * @param  string $messgae 发布信息
     * @return integer 订阅数
     */
    public function publish($channel, $messgae)
    {
        return $this->getRedis()->publish($channel, $messgae);
    }


    /************* 管理操作命令 *****************/

    /**
     * 测试当前链接是不是已经失效，没有失效返回+PONG，失效返回false
     * 
     * @return string|false
     */
    public function ping()
    {
        return $this->getRedis()->ping();
    }

    /**
     * 密码认证
     *
     * @param  string $auth 密码
     * @return boolean
     */
    public function auth($auth)
    {
        return $this->getRedis()->auth($auth);
    }

    /**
     * 选择数据库
     *
     * @param integer $dbId 数据库ID号
     * @return boolean
     */
    public function select($dbId)
    {
        return $this->getRedis()->select($dbId);
    }

    /**
     * 移动一个KEY-VALUE到另一个DB
     *
     * @param  string $key     key值
     * @param  integer $dbindex 要移动到的数据库ID
     * @return boolean
     */
    public function move($key, $dbindex)
    {
        return $this->getRedis()->move($key, $dbindex);
    }

    /**
     * 重命名一个KEY
     *
     * @param  string $key     键名
     * @param  string $new_key 新的键名
     * @return boolean
     */
    public function rename($key, $new_key)
    {
        return $this->getRedis()->rename($key, $new_key);
    }

    /**
     * 复制一个KEY的VALUE到一个新的KEY
     *
     * @param  string $key     键名
     * @param  string $new_key 新的键名
     * @return boolean
     */
    public function renameNx($key, $new_key)
    {
        return $this->getRedis()->renameNx($key, $new_key);
    }

    /**
     * 清空当前数据库
     *
     * @return boolean
     */
    public function flushDB()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * 清空所有数据库
     *
     * @return boolean
     */
    public function flushAll()
    {
        return $this->getRedis()->flushAll();
    }

    /**
     * 返回当前库状态
     *
     * @return array
     */
    public function info()
    {
        return $this->getRedis()->info();
    }

    /**
     * 重置状态
     *
     * @return boolean
     */
    public function resetStat()
    {
        return $this->getRedis()->resetStat();
    }

    /**
     * 同步保存数据到磁盘
     * 
     * @return boolean
     */
    public function save()
    {
        return $this->getRedis()->save();
    }

    /**
     * 异步保存数据到磁盘
     * 
     * @return boolean
     */
    public function bgSave()
    {
        return $this->getRedis()->bgSave();
    }

    /**
     * 返回最后保存到磁盘的时间
     */
    public function lastSave()
    {
        return $this->getRedis()->lastSave();
    }

    /**
     * 返回key,支持*多个字符，?一个字符
     * 只有*　表示全部
     *
     * @param string $key   键
     * @return array
     */
    public function keys($key)
    {
        return $this->getRedis()->keys($key);
    }

    /**
     * 返回一个key的数据类型
     *
     * @param  string $key 键
     * @return string
     */
    public function type($key)
    {
        return $this->getRedis()->type($key);
    }

    /**
     * 发送一个字符串到Redis,返回一个相同的字符串
     *
     * @param  string $string 字符串
     * @return string
     */
    public function out($string)
    {
        return $this->getRedis()->echo($string);
    }

    /**
     * 为一个key设定过期时间, 单位为秒
     *
     * @param string  $key       键
     * @param integer $expire    过期时间，单位秒
     * @return boolean
     */
    public function expire($key, $expire)
    {
        return $this->getRedis()->expire($key, $expire);
    }

    /**
     * 为一个key设定过期时间, 单位为毫秒
     *
     * @param  string  $key    键
     * @param  integer $expire 过期时间，单位毫秒
     * @return boolean
     */
    public function pexpire($key, $expire)
    {
        return $this->getRedis()->pexpire($key, $expire);
    }

    /**
     * 为一个key设定生命周期
     *
     * @param  string $key    key名称
     * @param  integer $expire 过期时间, Unix时间戳
     * @return boolean
     */
    public function expireAt($key, $expire)
    {
        return $this->getRedis()->expireAt($key, $expire);
    }

    /**
     * 删除一个KEY的生命周期设置
     *
     * @param  string $key 键
     * @return boolean
     */
    public function persist($key)
    {
        return $this->getRedis()->persist($key);
    }

    /**
     * 返回一个key还有多久过期，单位秒
     *
     * @param string $key   键
     * @return integer
     */
    public function ttl($key)
    {
        return $this->getRedis()->ttl($key);
    }

    /**
     * 返回一个key还有多久过期, 单位毫秒
     *
     * @param  string $key 键
     * @return integer
     */
    public function pttl($key)
    {
        return $this->getRedis()->pttl($key);
    }

    /**
     * 关闭服务器链接
     * 
     * @return void
     */
    public function close()
    {
        return $this->getRedis()->close();
    }

    /**
     * 返回当前数据库key数量
     * 
     * @return integer
     */
    public function dbSize()
    {
        return $this->getRedis()->dbSize();
    }

    /**
     * 返回一个随机key
     * 
     * @return string
     */
    public function randomKey()
    {
        return $this->getRedis()->randomKey();
    }

    /**
     * 设置客户端的选项
     *
     * @param string $key   键
     * @param string $value 值
     * @return boolean
     */
    public function setOption($key, $value)
    {
        return $this->getRedis()->setOption($key, $value);
    }

    /**
     * 取得客户端的选项
     *
     * @param string $key   键
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->getRedis()->getOption($key);
    }

    /**
     * 使用aof来进行数据库持久化
     *
     * @return boolean
     */
    public function bgrewriteaof()
    {
        return $this->getRedis()->bgrewriteaof();
    }

    /**
     * 选择从服务器
     *
     * @param  string  $ip   IP
     * @param  integer $port 端口
     * @return boolean
     */
    public function slaveof($ip, $port)
    {
        return $this->getRedis()->slaveof($ip, $port);
    }

    /**
     * 声明一个对象，并指向KEY
     *
     * @param  string $type 检索的类型
     * @param  string $key  key名
     * @return mixed
     */
    public function object($type, $key)
    {
        if (!in_array($type, ['encoding', 'refcount', 'idletime'])) {
            throw new InvalidArgumentException('object type faild');
        }
        return $this->getRedis()->object($type, $key);
    }

    /**
     * 设置Redis系统配置
     *
     * @param string $key   键
     * @param string $value 值
     * @return boolean
     */
    public function setConfig($key, $value)
    {
        return $this->getRedis()->config('SET', $key, $value);
    }

    /**
     * 获取Redis系统配置, *表示所有
     *
     * @param string $key   键，*表示所有
     * @return mixed
     */
    public function getConfig($key)
    {
        return $this->getRedis()->config('GET', $key);
    }

    /**
     * 在服务器端执行LUA脚本
     *
     * @param  string $script Lua脚本
     * @return mixed
     */
    public function run($script)
    {
        return $this->getRedis()->eval($script);
    }

    /**
     * 取得最后的错误消息
     *
     * @return mixed
     */
    public function getLastError()
    {
        return $this->getRedis()->getLastError();
    }

    /**
     * 把一个KEY从Redis中销毁, 可以使用RESTORE函数恢复出来。
     * 使用DUMP销毁的VALUE, 函数将返回这个数据在Redis中的二进制内存地址
     *
     * @param  string $key 键
     * @return mixed
     */
    public function dump($key)
    {
        return $this->getRedis()->dump($key);
    }

    /**
     * 恢复DUMP函数销毁的VALUE到一个新的KEY上
     *
     * @param  string  $key     新的key名
     * @param  integer $expire  生存时间, 0则不设置
     * @param  string  $value   dump返回的地址值
     * @param  mixed   $options 额外参数
     * @return mixed
     */
    public function restore($key, $expire, $value, $options = null)
    {
        return $this->getRedis()->restore($key, $expire, $value, $options);
    }

    /**
     * 返回当前Redis服务器的生存时间
     *
     * @return integer
     */
    public function time()
    {
        return $this->getRedis()->time();
    }
}
