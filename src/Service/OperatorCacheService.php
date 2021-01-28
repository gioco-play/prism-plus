<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Psr\Container\ContainerInterface;

/**
 * 運營商快取
 * Class CacheService
 * @package GiocoPlus\PrismPlus\Service
 */
class OperatorCacheService
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
     * 營運商基本資料
     * @param string $code
     * @Cacheable(prefix="op_basic", ttl=600, value="_#{code}", listener="op_basic_cache")
     */
    public function basic(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "code" => 1,
                "name" => 1,
                "status" => 1,
                "operator_token" => 1,
                "secret_key" => 1,
                "currency" => 1,
                "website" => 1
            ]
        ]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 總開關
     * @param string $code
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="op_main_switch", ttl=600, value="_#{code}", listener="op_main_switch_cache")
     */
    public function mainSwitch(string $code) {
        $this->dbDefaultPool();
        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "code" => 1,
                "status" => 1,
                "main_switch" => 1
            ]
        ]));

        if ($data) {
            return [
                "code" => $data['code'],
                "status" => $data['status'],
                'switch' => json_decode(json_encode($data['main_switch']), true)
            ];
        }

        return null;
    }

    /**
     * 遊戲商 開關 / 配置
     * @param string $code
     * @param string $vendorCode
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="op_vendor_setting", ttl=600, value="_#{code}_#{vendorCode}", listener="op_vendor_setting_cache")
     */
    public function vendorSetting(string $code, string $vendorCode) {
        $this->dbDefaultPool();
        $vendor = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "code" => 1,
                "status" => 1,
                "currency" => 1,
                "main_switch" => 1,
                "vendor_switch.{$vendor}" => 1,
                "vendors.{$vendor}" => 1,
            ]
        ]));

        if ($data) {
            return [
                "code" => $data['code'],
                "status" => $data['status'],
                "currency" => $data['currency'],
                "main_switch" => json_decode(json_encode($data['main_switch']), true),
                "switch" => json_decode(json_encode($data['vendor_switch']->$vendor), true),
                "vendor" => json_decode(json_encode($data['vendors']->$vendor), true)
            ];
        }

        return null;
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @Cacheable(prefix="op_currency_rate", ttl=600, value="_#{code}", listener="op_currency_rate_cache")
     */
    public function currencyRate(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "currency_rate" => 1,
            ]
        ]));

        if ($data) {
            return collect($data['currency_rate'])->pluck('rate', 'vendor')->toArray();
        }

        return [];
    }

    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return array
     * @Cacheable(prefix="op_block_game", ttl=600, value="_#{code}_#{vendorCode}", listener="op_block_game_cache")
     */
    public function blockGames(string $code, string $vendorCode) {
        $this->dbDefaultPool();
        $vendor = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "game_blocklist.{$vendor}" => 1,
            ]
        ]));

        if ($data&&isset($data['game_blocklist'])&&isset($data['game_blocklist']->$vendor)) {
            return $data['game_blocklist']->$vendor;
        }
        return [];
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_api_whitelist", ttl=600, value="_#{code}", listener="op_api_whitelist_cache")
     */
    public function apiWhitelist(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "code" => 1,
                "operator_token" => 1,
                "secret_key" => 1,
                "status" => 1,
                "api_whitelist" => 1,
            ]
        ]));

        if ($data) {
            return $data;
        }
        return [];
    }

    /**
     * 運營商 DB 配置
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_db_setting", ttl=600, value="_#{code}", listener="op_db_setting_cache")
     */
    public function dbSetting(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "db" => 1,
            ]
        ]));

        if ($data&&isset($data['db'])) {
            return $data['db'];
        }
        return [];
    }

    /**
     * 運營商 類單一錢包配置
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_seamless_setting", ttl=600, value="_#{code}", listener="op_seamless_setting_cache")
     */
    public function seamlessSetting(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => $code
                    ]
                ]
            ]
        ], [
            'projection' => [
                "seamless_setting" => 1,
            ]
        ]));

        if ($data&&isset($data['seamless_setting'])) {
            return $data['seamless_setting'];
        }
        return [];
    }

    /**
     * 營商遊戲拉單開關
     * @param string $vendorCode
     * @return array
     * @Cacheable(prefix="grabber_log_enable", ttl=600, value="_#{vendorCode}", listener="grabber_log_enable_cache")
     */
    public function grabberLogEnable(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('operators', [
            "status" => "online",
            "main_switch.grabber_log_on" => true,
            "vendor_switch.{$vendorCode}.grabber_log_on" => true
        ], [
            'projection' => [
                "code" => 1,
                "vendors.{$vendorCode}" => 1,
            ]
        ]);

        if ($data) {
            return $data;
        }
        return [];
    }
}