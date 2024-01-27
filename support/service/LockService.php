<?php

declare(strict_types=1);

namespace support\service;

use mon\util\Common;
use mon\util\Instance;

/**
 * 基于Redis的程序业务锁
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0 2018-05-20
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
     * @return array
     */
    public function lock(string $uid, string $logic_id, string $ip, string $app_id = 'gaia', int $lock_time = 5): array
    {
        // 分的top-level key名
        $lock_key = self::MARK . $app_id . ':' . $uid . ':' . $logic_id;
        // 分的top-level v值 (注意：解铃还需系铃人)
        $lock_value = $ip . '_' . $uid . '_' . $logic_id . '_' . Common::instance()->uuid();
        // 总的统计hash的 k名
        $hash_table_all_key = self::HASH_ALL_COUNT_KEY_PREFIX . $app_id;
        // 总的统计hash里,更新次数的field
        $hash_table_all_count_field = self::HASH_ALL_COUNT_FIELD_PREFIX . $lock_key;

        // 判断是否已存在锁
        if ($this->getRedis()->exists($lock_key)) {
            // 存在锁，不允许枷锁，返回错误
            return $this->result(-3, '加锁失败, 锁已经存在 => ' . $lock_key);
        }
        // 不存在锁，设置锁
        $lock = $this->getRedis()->setex($lock_key, $lock_time, $lock_value);
        if ($lock) {
            // 成功获得锁(锁上了指定资源),最多 $lock_time 的时间给你执行对应操作,然后回来再释放锁

            // 记录锁的数量
            $count = $this->getRedis()->hincrby($hash_table_all_key, $hash_table_all_count_field, 1);
            if (!is_numeric($count)) {
                return $this->result(-2, '写总的hashtable失败 => ' . $lock_key);
            }

            // 加锁成功
            $data = [
                'lock_key' => $lock_key,
                'lock_value' => $lock_value,
                'lock_cnt' => $count,
                'app_id' => $app_id
            ];
            return $this->result(1, 'OK', $data);
        }

        // 设置锁失败
        return $this->result(-1, '设置锁失败 => ' . $lock_key);
    }

    /**
     * 解锁，实现解铃还需系铃人
     *
     * @param array $data   加锁成功后返回的data字段数据
     * @return array
     */
    public function unLock(array $data): array
    {
        $app_id = $data['app_id'] ?: '';
        $lock_key = $data['lock_key'] ?: '';
        $lock_value = $data['lock_value'] ?: '';
        $lock_cnt = $data['lock_cnt'] ?: '';

        $hash_table_all_key = self::HASH_ALL_COUNT_KEY_PREFIX . $app_id;
        $hash_table_all_count_field = self::HASH_ALL_COUNT_FIELD_PREFIX . $lock_key;

        if (empty($lock_key) || empty($app_id) || empty($lock_value) || empty($lock_cnt)) {
            return $this->result(-4, '解锁参数错误');
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
        switch ($ret) {
            case 0:
                // 解锁失败(无权限),该锁不属于你的. 可能情况:
                // 1) 解锁已经超过事务的等待时间了,由其他竞争者重新获得了锁,你当前已经没有权限释放这个锁. 
                // 2) 调用方失误,传递的lock_key和lock_value不正确
                return $this->result(-5, '解锁失败(无权限) => ' . $lock_key);
            case 1:
                // 解锁成功 (解铃还需系铃人)
                return $this->result(1, 'OK');
            case 2:
                // 解锁失败(过期干净), 解锁已经超过事务的等待时间了
                return $this->result(-6, '解锁失败(过期干净) => ' . $lock_key);
            case 3:
                // 解锁失败(过期受污染), 解锁已经超过事务的等待时间了.
                return $this->result(-7, '解锁失败(过期受污染) => ' . $lock_key);
            default:
                return $this->result(0, '解锁失败(异常)');
        }
    }

    /**
     * 返回统一格式结果集
     *
     * @param integer $code 状态码
     * @param string $msg   描述
     * @param array $data   结果
     * @return array
     */
    protected function result(int $code, string $msg = 'OK', array $data = []): array
    {
        return [
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data,
        ];
    }
}
