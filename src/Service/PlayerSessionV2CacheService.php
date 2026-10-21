<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\RedisFactory;

/**
 * 玩家 Session V2
 *
 * - 進入遊戲時產生隨機 token，同一玩家在同一 vendor 只有一個 session，新登入會讓舊 token 失效
 * - TTL 為閒置時間，touch 會重新計時
 * - 寫入皆以 Lua 原子執行，只支援單機 / sentinel，不支援 Redis Cluster
 * - settle / cancel / rollback 不要靠 token，改用帳號 + 注單號
 *
 * Class PlayerSessionV2CacheService
 * @package GiocoPlus\PrismPlus\Service
 */
class PlayerSessionV2CacheService
{
    /**
     * + token → Session JSON
     */
    const TOKEN_KEY_PREFIX = 'player_session_token_';

    /**
     * + lower(vendor_code)_vendor_account → token
     */
    const OWNER_KEY_PREFIX = 'player_session_owner_';

    /**
     * @Inject()
     * @var RedisFactory
     */
    protected $redisFactory;

    /**
     * 建立玩家 session（只有按下「進入遊戲」才呼叫，重新整理或重連不能呼叫）
     * @param string $vendorAccount launch 時給 vendor 的帳號字串，需全域唯一
     * @param string $vendorCode
     * @param array $params 各 vendor 自訂內容，可為清單或 key => value，讀出時格式不變
     * @param int $ttl 閒置 TTL（秒），>= 1，預設 3600
     * @param int $tokenLen 32 或 64
     * @return string token
     */
    public function create(string $vendorAccount, string $vendorCode, array $params = [], int $ttl = 3600, int $tokenLen = 64): string
    {
        if ($vendorAccount === '' || $vendorCode === '') {
            throw new \InvalidArgumentException('vendorAccount and vendorCode must not be empty');
        }
        if ($ttl < 1) {
            throw new \InvalidArgumentException("ttl must be >= 1 second, got {$ttl}");
        }
        if ($tokenLen !== 32 && $tokenLen !== 64) {
            throw new \InvalidArgumentException("token length must be 32 or 64, got {$tokenLen}");
        }

        $token = bin2hex(random_bytes(intdiv($tokenLen, 2)));

        $data = json_encode([
            'vendor_account' => $vendorAccount,
            'vendor_code' => $vendorCode,
            'params' => $params,
            'ttl_seconds' => $ttl,
            'created_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($data === false) {
            throw new \RuntimeException('player session json encode failed: ' . json_last_error_msg());
        }

        // KEYS: token key, owner key / ARGV: session JSON, ttl 毫秒, token, token key prefix
        $script = <<<'LUA'
redis.call('SET', KEYS[1], ARGV[1], 'PX', ARGV[2])
local old = redis.call('GET', KEYS[2])
redis.call('SET', KEYS[2], ARGV[3], 'PX', ARGV[2])
if old and old ~= ARGV[3] then redis.call('DEL', ARGV[4] .. old) end
return 1
LUA;
        $ownerKey = self::OWNER_KEY_PREFIX . strtolower($vendorCode) . '_' . $vendorAccount;
        $this->runScript($script, [self::TOKEN_KEY_PREFIX . $token, $ownerKey], [$data, (string)($ttl * 1000), $token, self::TOKEN_KEY_PREFIX]);

        return $token;
    }

    /**
     * 取得玩家 session，不延長時間
     * @param string $token
     * @return array|null 找不到回 null
     */
    public function fetch(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $redis = $this->redisFactory->get('default');
        $v = $redis->get(self::TOKEN_KEY_PREFIX . $token);
        if ($v === false) {
            return null;
        }
        return $this->decode($v);
    }

    /**
     * 取得玩家 session 並延長閒置 TTL（bet / getbalance 使用）
     * @param string $token
     * @return array|null 找不到回 null
     */
    public function touch(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        // 找不到回 0（phpredis 在 Lua 回 nil 與執行錯誤時都會得到 false，無法區分）
        // KEYS: token key / ARGV: token, owner key prefix
        $script = <<<'LUA'
local v = redis.call('GET', KEYS[1])
if not v then return 0 end
local s = cjson.decode(v)
local ms = s.ttl_seconds * 1000
redis.call('PEXPIRE', KEYS[1], ms)
local ok = ARGV[2] .. string.lower(s.vendor_code) .. '_' .. s.vendor_account
if redis.call('GET', ok) == ARGV[1] then redis.call('PEXPIRE', ok, ms) end
return v
LUA;
        $v = $this->runScript($script, [self::TOKEN_KEY_PREFIX . $token], [$token, self::OWNER_KEY_PREFIX]);
        if ($v === 0) {
            return null;
        }
        return $this->decode($v);
    }

    /**
     * 清除指定 token 的玩家 session，不存在也視為成功
     * @param string $token
     */
    public function clear(string $token): void
    {
        if ($token === '') {
            return;
        }
        // KEYS: token key / ARGV: token, owner key prefix
        $script = <<<'LUA'
local v = redis.call('GET', KEYS[1])
if not v then return 0 end
redis.call('DEL', KEYS[1])
local s = cjson.decode(v)
local ok = ARGV[2] .. string.lower(s.vendor_code) .. '_' .. s.vendor_account
if redis.call('GET', ok) == ARGV[1] then redis.call('DEL', ok) end
return 1
LUA;
        $this->runScript($script, [self::TOKEN_KEY_PREFIX . $token], [$token, self::OWNER_KEY_PREFIX]);
    }

    /**
     * 清除玩家在該 vendor 目前的 session，不存在也視為成功（踢整個玩家要對每個 vendor 各呼叫一次）
     * @param string $vendorAccount
     * @param string $vendorCode
     */
    public function clearByAccount(string $vendorAccount, string $vendorCode): void
    {
        // KEYS: owner key / ARGV: token key prefix
        $script = <<<'LUA'
local t = redis.call('GET', KEYS[1])
if t then redis.call('DEL', ARGV[1] .. t) end
redis.call('DEL', KEYS[1])
return 1
LUA;
        $ownerKey = self::OWNER_KEY_PREFIX . strtolower($vendorCode) . '_' . $vendorAccount;
        $this->runScript($script, [$ownerKey], [self::TOKEN_KEY_PREFIX]);
    }

    /**
     * 查玩家在該 vendor 目前有效的 token，不延長時間
     * @param string $vendorAccount
     * @param string $vendorCode
     * @return string|null 找不到回 null
     */
    public function tokenOf(string $vendorAccount, string $vendorCode): ?string
    {
        $redis = $this->redisFactory->get('default');
        $token = $redis->get(self::OWNER_KEY_PREFIX . strtolower($vendorCode) . '_' . $vendorAccount);
        if ($token === false) {
            return null;
        }
        return $token;
    }

    /**
     * 執行 Lua，phpredis 執行錯誤時回 false 而不丟例外，這裡統一轉成例外
     * @param string $script
     * @param array $keys
     * @param array $args
     * @return mixed
     */
    private function runScript(string $script, array $keys, array $args)
    {
        $redis = $this->redisFactory->get('default');
        $r = $redis->eval($script, array_merge($keys, $args), count($keys));
        if ($r === false) {
            throw new \RuntimeException('player session redis eval failed');
        }
        return $r;
    }

    /**
     * @param string $v
     * @return array
     */
    private function decode(string $v): array
    {
        $s = json_decode($v, true);
        if (!is_array($s)) {
            throw new \RuntimeException('player session json decode failed: ' . json_last_error_msg());
        }
        // params 可能存成 null
        $s['params'] = $s['params'] ?? [];
        return $s;
    }
}
