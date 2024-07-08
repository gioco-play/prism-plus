<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\RedisFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 運營商清除快取
 * Class CacheFlushService
 * @package GiocoPlus\PrismPlus\Service
 */
class OperatorCacheFlushService
{
    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @Inject()
     * @var RedisFactory
     */
    protected $redisFactory;

    /**
     * 營運商基本資料
     * @param string $code
     * @return bool
     * @throws \Exception
     */
    public function basic(string $code) {
        $code = strtoupper($code);
        $key = 'op_basic_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 總開關
     * @param string $code
     * @return bool
     * @throws \Exception
     */
    public function mainSwitch(string $code) {
        $code = strtoupper($code);
        $key = 'op_main_switch_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }
    /**
     * 遊戲商 開關 / 配置
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function vendorSetting(string $code, string $vendorCode) {
        $code = strtoupper($code);
        $vendorCode = strtolower($vendorCode);
        $key = 'op_vendor_setting_' . $code . '_' . $vendorCode;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @return bool
     */
    public function currencyRate(string $code) {
        $code = strtoupper($code);
        $key = 'op_currency_rate_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }
    
    /**
     * 營運商幣別對應
     * @param string $code
     * @return bool
     */
    public function currency(string $code) {
        $code = strtoupper($code);
        $key = 'op_currency_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }
    
    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function blockGames(string $code, string $vendorCode) {
        $code = strtoupper($code);
        $vendorCode = strtolower($vendorCode);
        $key = 'op_block_game_' . $code . '_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return bool
     */
    public function apiWhitelist(string $code) {
        $code = strtoupper($code);
        $key = 'op_api_whitelist_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 運營商 DB 配置
     * @param string $code
     * @return bool
     */
    public function dbSetting(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_db_setting_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }

   /**
    * 運營商 k8s隸屬 配置
    *
    * @param string $code
    * @return bool
    */
    public function k8sSetting(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_k8s_setting_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }

    /**
     * 運營商 類單一錢包配置
     * @param string $code
     * @return bool
     */
    public function seamlessSetting(string $code) {
        $code = strtoupper($code);
        $key = 'op_seamless_setting_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 營商遊戲拉單開關
     * @param string $vendorCode
     * @return bool
     */
    public function grabberLogEnable(string $vendorCode)
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'grabber_log_enable_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 檢查遊戲類型
     * @param string $code
     * @param string $gameType
     */
    public function checkGameType(string $code, string $gameType)
    {
        $this->dispatcher->dispatch(new DeleteListenerEvent('check_gametype_cache', [
            'code' => strtoupper($code),
            'gameType' => $gameType
        ]));
        return true;
    }
}
