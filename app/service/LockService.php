<?php

declare(strict_types=1);

namespace app\service;

use mon\util\Common;
use RuntimeException;
use mon\util\Instance;

/**
 * 基于Redis的程序业务锁
 *
 * @author Mon <985558837@qq.com>
 * @version 1.1 2025-10-16
 */
class LockService
{
    use Instance;

    /**
     * 锁前缀
     */
    const MARK = 'MON_LOCK:';

    /**
     * hash统计所有key名前缀
     */
    const HASH_ALL_COUNT_KEY_PREFIX = 'HT_LOCK:';

    /**
     * hash统计所有字段前缀
     */
    const HASH_ALL_COUNT_FIELD_PREFIX = 'CNT:';

    /**
     * 获取Redis实例
     *
     * @return RedisService
     */
    public function getRedis(): RedisService
    {
        return RedisService::instance();
    }

    /**
     * 加锁
     *
     * @param string  $uid          用户唯一标识
     * @param string  $logic_id     业务锁标识
     * @param string  $ip           调用IP
     * @param string  $app_id       应用标识
     * @param integer $lock_time    最大锁定时间，单位秒
     * @throws RuntimeException
     * @return array
     */
    public function lock(string $uid, string $logic_id, string $ip, string $app_id = 'gaia', int $lock_time = 5): array
    {
        // 分的top-level key名
        $lock_key = self::MARK . $app_id . ':' . $uid . ':' . $logic_id;
        // 分的top-level v值 (注意：解铃还需系铃人)
        $lock_value = $ip . '_' . $uid . '_' . $logic_id . '_' . Common::uuid();
        // 总的统计hash的 k名
        $hash_table_all_key = self::HASH_ALL_COUNT_KEY_PREFIX . $app_id;
        // 总的统计hash里,更新次数的field
        $hash_table_all_count_field = self::HASH_ALL_COUNT_FIELD_PREFIX . $lock_key;

        // 判断是否已存在锁
        if ($this->getRedis()->exists($lock_key)) {
            // 存在锁，不允许枷锁，返回错误
            throw new RuntimeException('加锁失败, 锁已经存在 => ' . $lock_key);
        }
        // 不存在锁，设置锁
        $lock = $this->getRedis()->setex($lock_key, $lock_time, $lock_value);
        // 成功获得锁(锁上了指定资源),最多 $lock_time 的时间给你执行对应操作,然后回来再释放锁
        if ($lock) {
            // 记录锁的数量
            $count = $this->getRedis()->hincrby($hash_table_all_key, $hash_table_all_count_field, 1);
            if (!is_numeric($count)) {
                throw new RuntimeException('写总的hashtable校验失败 => ' . $lock_key);
            }

            // 加锁成功
            return [
                'lock_key' => $lock_key,
                'lock_value' => $lock_value,
                'lock_cnt' => $count,
                'app_id' => $app_id
            ];
        }

        // 设置锁失败
        throw new RuntimeException('设置锁失败 => ' . $lock_key);
    }

    /**
     * 解锁，实现解铃还需系铃人
     *
     * @param array $data   加锁成功后返回的data字段数据
     * @throws RuntimeException
     * @return bool
     */
    public function unLock(array $data): bool
    {
        $app_id = $data['app_id'] ?: '';
        $lock_key = $data['lock_key'] ?: '';
        $lock_value = $data['lock_value'] ?: '';
        $lock_cnt = $data['lock_cnt'] ?: '';

        $hash_table_all_key = self::HASH_ALL_COUNT_KEY_PREFIX . $app_id;
        $hash_table_all_count_field = self::HASH_ALL_COUNT_FIELD_PREFIX . $lock_key;

        if (empty($lock_key) || empty($app_id) || empty($lock_value) || empty($lock_cnt)) {
            throw new RuntimeException('解锁参数错误');
        }

        // Lua脚本
        $redis_script = <<<EOT
if redis.call('ttl', KEYS[1]) == -2 then 
    if redis.call('hget', KEYS[2], KEYS[3]) == ARGV[2] then 
        return 2 
    else
        return 3
    end
elseif redis.call('get', KEYS[1]) == ARGV[1] then 
    return redis.call('del', KEYS[1]) 
else 
    return 0 
end
EOT;
        $input = [$lock_key, $hash_table_all_key, $hash_table_all_count_field, $lock_value, $lock_cnt];
        // 执行Lua脚本, 注意，eval方法第二个参数必须是索引数组代表入参，第三个参数必须是3
        // 原因：原生redia会将前3个脚本参数认为是 KEYS 后面的参数认为是 ARGV 
        $ret = $this->getRedis()->eval($redis_script, $input, 3);
        if ($ret == 1) {
            return true;
        }
        $msg = '解锁失败(异常)';
        switch ($ret) {
            case 0:
                $msg = '解锁失败(无权限)';
                break;
            case 2:
                $msg = '解锁失败(过期干净)';
                break;
            case 3:
                $msg = '解锁失败(过期受污染)';
                break;
        }
        throw new RuntimeException($msg . ' => ' . $lock_key);
    }
}
