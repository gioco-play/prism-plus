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
     * @return mixed|null
     */
    public function basic(string $code)
    {
        return $this->basicCache(strtoupper($code));
    }

    /**
     * 營運商基本資料
     * @param string $code
     * @Cacheable(prefix="op_basic", value="_#{code}", listener="op_basic_cache")
     */
    private function basicCache(string $code) {
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
                        '$eq' => strtolower($code)
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
                "website" => 1,
                "member_already"=>1
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
     */
    public function mainSwitch(string $code)
    {
        return $this->mainSwitchCache(strtoupper($code));
    }

    /**
     * 總開關
     * @param string $code
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="op_main_switch", value="_#{code}", listener="op_main_switch_cache")
     */
    private function mainSwitchCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => strtolower($code)
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
     * @return array|null
     */
    public function vendorSetting(string $code, string $vendorCode)
    {
        return $this->vendorSettingCache(strtoupper($code), strtolower($vendorCode));
    }

    /**
     * 遊戲商 開關 / 配置
     * @param string $code
     * @param string $vendorCode
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="op_vendor_setting", value="_#{code}_#{vendorCode}", listener="op_vendor_setting_cache")
     */
    private function vendorSettingCache(string $code, string $vendorCode) {
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
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "code" => 1,
                "status" => 1,
                "currency" => 1,
                "website" => 1,
                "main_switch" => 1,
                "vendor_switch.{$vendor}" => 1,
                "vendors.{$vendor}" => 1
            ]
        ]));

        if ($data) {
            return [
                "code" => $data['code'],
                "status" => $data['status'],
                "currency" => $data['currency'],
                "website" => $data['website'],
                "main_switch" => json_decode(json_encode($data['main_switch']), true),
                "switch" => json_decode(json_encode($data['vendor_switch']->$vendor), true),
                "vendor" => json_decode(json_encode($data['vendors']->$vendor), true),
                "vendor_code" => $vendor
            ];
        }

        return null;
    }

    /**
     * 營運商幣值表
     * @param string $code
     */
    public function currencyRate(string $code)
    {
        return $this->currencyRateCache(strtoupper($code));
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @Cacheable(prefix="op_currency_rate", value="_#{code}", listener="op_currency_rate_cache")
     */
    private function currencyRateCache(string $code) {
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
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "currency_rate" => 1,
            ]
        ]));

        if ($data) {
            $rates = json_decode(json_encode($data['currency_rate']), true);
            $_rates = [];
            foreach ($rates as $vendor => $value) {
                $_rates[$vendor] = $value["rate"];
            }
            return $_rates;
        }

        return [];
    }

    /**
     * 營運商幣別對應
     * @param string $code
     */
    public function currency(string $code)
    {
        return $this->currencyCache(strtoupper($code));
    }

    /**
     * 營運商幣別對應
     * @param string $code
     * @Cacheable(prefix="op_currency", value="_#{code}", listener="op_currency_cache")
     */
    private function currencyCache(string $code) {
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
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "currency_rate" => 1,
            ]
        ]));

        if ($data) {
            $rates = json_decode(json_encode($data['currency_rate']), true);
            $_currencies = [];
            foreach ($rates as $vendor => $value) {
                $_currencies[$vendor] = $value["vendor"];
            }
            return $_currencies;
        }

        return [];
    }

    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return array
     */
    public function blockGames(string $code, string $vendorCode): array
    {
        return $this->blockGamesCache(strtoupper($code), strtolower($vendorCode));
    }

    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return array
     * @Cacheable(prefix="op_block_game", value="_#{code}_#{vendorCode}", listener="op_block_game_cache")
     */
    private function blockGamesCache(string $code, string $vendorCode) {
        $this->dbDefaultPool();
//        $vendor = strtolower($vendorCode);
        $data = current($this->mongodb->fetchAll('operators', [
            '$or' => [
                [
                    'code' => [
                        '$eq' => $code
                    ]
                ],
                [
                    'operator_token' => [
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "game_blocklist.{$vendorCode}" => 1,
            ]
        ]));

        if ($data&&isset($data['game_blocklist'])&&isset($data['game_blocklist']->$vendorCode)) {
            return $data['game_blocklist']->$vendorCode;
        }
        return [];
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return array
     */
    public function apiWhitelist(string $code): array
    {
        return $this->apiWhitelistCache(strtoupper($code));
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_api_whitelist", value="_#{code}", listener="op_api_whitelist_cache")
     */
    private function apiWhitelistCache(string $code) {
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
                        '$eq' => strtolower($code)
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
     */
    public function dbSetting(string $code)
    {
        return $this->dbSettingCache(strtoupper($code));
    }

    /**
     * 運營商 DB 配置
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_db_setting", value="_#{code}", listener="op_db_setting_cache")
     */
    private function dbSettingCache(string $code) {
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
                        '$eq' => strtolower($code)
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
     * 運營商 k8s隸屬 配置
     * @param string $code
     */
    public function k8sSetting(string $code)
    {
        return $this->k8sSettingCache(strtoupper($code));
    }

    /**
     * 運營商 k8s隸屬 配置
     * @param string $code
     * @Cacheable(prefix="op_k8s_setting", value="_#{code}", listener="op_k8s_setting_cache")
     */
    private function k8sSettingCache(string $code) {
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
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "k8s_group" => 1,
            ]
        ]));

        if ($data && isset($data['k8s_group'])) {
            return $data['k8s_group'];
        }
        return "";
    }

    /**
     * 運營商 類單一錢包配置
     * @param string $code
     * @return array
     */
    public function seamlessSetting(string $code): array
    {
        return $this->seamlessSettingCache(strtoupper($code));
    }

    /**
     * 運營商 類單一錢包配置
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_seamless_setting", value="_#{code}", listener="op_seamless_setting_cache")
     */
    private function seamlessSettingCache(string $code) {
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
                        '$eq' => strtolower($code)
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
     */
    public function grabberLogEnable(string $vendorCode)
    {
        return $this->grabberLogEnableCache(strtolower($vendorCode));
    }

    /**
     * 營商遊戲拉單開關
     * @param string $vendorCode
     * @return array
     * @Cacheable(prefix="grabber_log_enable", value="_#{vendorCode}", listener="grabber_log_enable_cache")
     */
    private function grabberLogEnableCache(string $vendorCode) {
//        $vendorCode = strtolower($vendorCode);
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
            return json_decode(json_encode($data), true);
        }
        return [];
    }

    /**
     * 檢查遊戲類型
     * @param string $code
     * @param string $gameType
     */
    public function checkGameType(string $code, string $gameType)
    {
        return $this->checkGameTypeCache(strtoupper($code), $gameType);
    }

    /**
     * 檢查遊戲類型
     * @param string $code
     * @param string $gameType
     * @Cacheable(prefix="check_gametype", value="_#{code}_#{gameType}", listener="check_gametype_cache")
     */
    private function checkGameTypeCache(string $code, string $gameType) {
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
                        '$eq' => strtolower($code)
                    ]
                ]
            ]
        ], [
            'projection' => [
                "vendor_switch" => 1,
            ]
        ]));

        if ($data) {
            $vendors = array_keys(json_decode(json_encode($data['vendor_switch']), true));
            $count = $this->mongodb->count('games', [
                'vendor_code' => [
                    '$in' => array_values($vendors),
                ],
                'game_type' => $gameType
            ]);

            if ($count) {
                return true;
            }
        }
        return false;
    }
}
