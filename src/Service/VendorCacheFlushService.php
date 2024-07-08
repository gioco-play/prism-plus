<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 遊戲商清除快取
 * Class VendorCacheFlushService
 * @package GiocoPlus\PrismPlus\Service
 */
class VendorCacheFlushService
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
     * 基本資料
     * @param string $code
     * @return bool
     */
    public function basic(string $code) {
        $code = strtolower($code);
        $key = 'vendor_basic_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 請求參數
     * @param string $code
     * @return bool
     */
    public function requestParams(string $code) {
        $code = strtolower($code);
        $key = 'vendor_request_params_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 投注紀錄欄位
     * @param string $code
     * @return bool
     */
    public function betlogField(string $code) {
        $code = strtolower($code);
        $key = 'vendor_betlog_field_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 IP 白名單
     * @param string $code
     * @return bool
     */
    public function ipWhitelist(string $code) {
        $code = strtolower($code);
        $key = 'vendor_ip_whitelist_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 支援語系
     * @param string $code
     * @return bool
     */
    public function language(string $code) {
        $code = strtolower($code);
        $key = 'vendor_language_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 支援幣別
     * @param string $code
     * @return bool
     */
    public function currency(string $code) {
        $code = strtolower($code);
        $key = 'vendor_currency_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     * @return bool
     */
    public function currencyRate(string $code) {
        $code = strtolower($code);
        $key = 'vendor_currency_rate_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲清單
     * @param string $code
     * @return bool
     */
    public function games(string $code) {
        $key = 'vendor_games_' . $code;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲
     * @param string $gameCode
     * @return bool
     */
    public function game(string $gameCode) {
        $gameCode = strtolower($gameCode);
        $key = 'vendor_game_' . $gameCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲代碼與ID對應
     * @param string $vendorCode
     * @return bool
     */
    public function gameCodeMapping(string $vendorCode) {
        $key = 'gamecode_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲代碼與供應商遊戲代碼對應
     * @param string $vendorCode
     * @return bool
     */
    public function vendorGameCodeMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gamecode_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲 ID 與代碼對應
     * @param string $vendorCode
     * @return bool
     */
    public function gameIdMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gameid_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     */
    public function gameNameMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gamename_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     */
    public function gameTypeMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gametype_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * GF遊戲代碼與類型對應
     * @param string $vendorCode
     */
    public function gfGameTypeMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'gf_gametype_mapping_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲維護清單
     * @param string $vendorCode
     */
    public function gameMaintainList(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_maintain_list_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲運作清單
     * @param string $vendorCode
     */
    public function gameWorkingList(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_working_list_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲狀態對照表
     * @param string $vendorCode
     */
    public function gameStatusList(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_status_list_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 錢包代碼
     * @return bool
     */
    public function walletCodes() {
        $key = 'vendor_wallet_code';

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }

    /**
     * 遊戲商線路群組狀態
     * @param string $vendorCode
     * @return bool
     */
    public function channelGroupStatus(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_channel_group_status_' . $vendorCode;

        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $this->redisFactory->get('v3')->del($key);
        }
        $this->redisFactory->get('default')->del($key);

        return true;
    }
}