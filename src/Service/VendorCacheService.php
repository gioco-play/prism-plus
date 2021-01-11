<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Psr\Container\ContainerInterface;

/**
 * 遊戲商快取
 * Class CacheService
 * @package GiocoPlus\PrismPlus\Service
 */
class VendorCacheService
{

    /**
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * MongoDb 連結池
     * @var string
     */
    protected $poolName = "default";

    public function __construct(ContainerInterface $container) {
        $this->mongodb = $container->get(MongoDb::class);
        $this->dbDefaultPool();
    }

    /**
     * 初始化
     */
    private function dbDefaultPool() {
        $this->mongodb->setPool($this->poolName);
    }

    /**
     * 基本資料
     * @param string $code
     * @Cacheable(prefix="vendor_basic", ttl=180, value="_#{code}", listener="vendor_basic_cache")
     */
    public function basic(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "code" => 1,
                "status" => 1,
                "name" => 1,
                "wallet_code" => 1,
                "seamless_enable" => 1,
                "account_delimiter" => 1
            ]
        ]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲商 請求參數
     * @param string $code
     * @Cacheable(prefix="vendor_request_params", ttl=180, value="_#{code}", listener="vendor_request_params_cache")
     */
    public function requestParams(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "request_params" => 1
            ]
        ]));

        if ($data) {
            return $data['request_params'];
        }

        return null;
    }

    /**
     * 遊戲商 投注紀錄欄位
     * @param string $code
     * @Cacheable(prefix="vendor_betlog_field", ttl=180, value="_#{code}", listener="vendor_betlog_field_cache")
     */
    public function betlogField(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "betlog_field" => 1
            ]
        ]));

        if ($data) {
            return $data['betlog_field'];
        }

        return null;
    }

    /**
     * 遊戲商 IP白名單
     * @param string $code
     * @Cacheable(prefix="vendor_ip_whitelist", ttl=180, value="_#{code}", listener="vendor_ip_whitelist_cache")
     */
    public function ipWhitelist(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "filter_ip" => 1,
                "ip_whitelist" => 1
            ]
        ]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲商 支援語系
     * @param string $code
     * @Cacheable(prefix="vendor_language", ttl=180, value="_#{code}", listener="vendor_language_cache")
     */
    public function language(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "language" => 1
            ]
        ]));

        if ($data) {
            return json_decode(json_encode($data['language']), true);
        }

        return null;
    }

    /**
     * 遊戲商 支援幣別
     * @param string $code
     * @Cacheable(prefix="vendor_currency", ttl=180, value="_#{code}", listener="vendor_currency_cache")
     */
    public function currency(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "currency" => 1
            ]
        ]));

        if ($data) {
            return json_decode(json_encode($data['currency']), true);
        }

        return null;
    }

    /**
     * 遊戲清單
     * @param string $code
     * @Cacheable(prefix="vendor_games", ttl=180, value="_#{code}", listener="vendor_games_cache")
     */
    public function games(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = $this->mongodb->fetchAll('games', ['vendor_code' => $code], [
            'projection' => [
                "updated_time" => 0,
                "created_time" => 0
            ]
        ]);

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲
     * @param string $gameCode
     * @Cacheable(prefix="vendor_game", ttl=30, value="_#{gameCode}", listener="vendor_game_cache")
     */
    public function game(string $gameCode) {
        $this->dbDefaultPool();
        $gameCode = strtolower($gameCode);
        $data = current($this->mongodb->fetchAll('games', ['game_code' => $gameCode], [
            'projection' => [
                "updated_time" => 0,
                "created_time" => 0
            ]
        ]));

        if ($data) {
            return json_decode(json_encode($data), true);
        }

        return null;
    }

    /**
     * 遊戲代碼與ID對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gamecode_mapping", ttl=1800, value="_#{vendorCode}", listener="vendor_gamecode_mapping_cache")
     */
    public function gameCodeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "game_id" => 1
            ]
        ]));

        if ($data) {
            return $walletCodes = collect($data)->pluck('game_code', 'game_id')->toArray();;
        }

        return null;
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gamename_mapping", ttl=1800, value="_#{vendorCode}", listener="vendor_gamename_mapping_cache")
     */
    public function gameNameMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "name" => 1
            ]
        ]));

        if ($data) {
            return collect($data)->pluck('game_code', 'name')->toArray();;
        }

        return null;
    }

    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gametype_mapping", ttl=1800, value="_#{vendorCode}", listener="vendor_gametype_mapping_cache")
     */
    public function gameTypeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "game_type" => 1
            ]
        ]));

        if ($data) {
            return collect($data)->pluck('game_code', 'game_type')->toArray();;
        }

        return null;
    }

    /**
     * 錢包代碼
     * @Cacheable(prefix="vendor_wallet_code", ttl=360, listener="vendor_wallet_code_cache")
     */
    public function walletCodes() {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('vendors');
        if ($data) {
            return collect($data)->pluck('wallet_code')->toArray();
        }
        return [];
    }

}