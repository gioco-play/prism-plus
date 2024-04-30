<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
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

    public function __construct(ContainerInterface $container)
    {
        $this->mongodb = $container->get(MongoDb::class);
        $this->dbDefaultPool();
    }

    /**
     * 初始化
     */
    private function dbDefaultPool()
    {
        $this->mongodb->setPool($this->poolName);
    }

    /**
     * 基本資料
     * @param string $code
     */
    public function basic(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_basic_' . $code;

        if (! ApplicationContext::getContainer()->has(Redis::class)){
            throw new \Exception('Please make sure if there is "CacheInterface" in the container');
        }
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = current($this->mongodb->fetchAll('vendors', [
                'code' => $code
            ], [
                'projection' => [
                    "code" => 1,
                    "status" => 1,
                    "name" => 1,
                    "wallet_code" => 1,
                    "seamless_enable" => 1,
                    "account_delimiter" => 1,
                    "wallet_type" => 1
                ]
            ]));
            if ($data) {
                $redis->setex($key, 60*60*1, json_encode($data));
                return $data;
            }

            return null;
        }
        return json_decode($redis->get($key), true);
    }

    /**
     * 遊戲商 請求參數
     * @param string $code
     */
    public function requestParams(string $code)
    {
        return $this->requestParamsCache(strtolower($code));
    }

    /**
     * 遊戲商 請求參數
     * @param string $code
     * @Cacheable(prefix="vendor_request_params", value="_#{code}", listener="vendor_request_params_cache")
     */
    private function requestParamsCache(string $code)
    {
        $this->dbDefaultPool();
//        $code = strtolower($code);
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
     */
    public function betlogField(string $code)
    {
        return $this->betlogFieldCache(strtolower($code));
    }

    /**
     * 遊戲商 投注紀錄欄位
     * @param string $code
     * @Cacheable(prefix="vendor_betlog_field", value="_#{code}", listener="vendor_betlog_field_cache")
     */
    private function betlogFieldCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "betlog_field" => 1
            ]
        ]));

        if ($data) {
            return json_decode(json_encode($data['betlog_field']), true);
        }

        return null;
    }

    /**
     * 遊戲商 IP白名單
     * @param string $code
     */
    public function ipWhitelist(string $code) {
        return $this->ipWhitelistCache(strtolower($code));
    }

    /**
     * 遊戲商 IP白名單
     * @param string $code
     * @Cacheable(prefix="vendor_ip_whitelist", value="_#{code}", listener="vendor_ip_whitelist_cache")
     */
    private function ipWhitelistCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
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
     */
    public function language(string $code)
    {
        return $this->languageCache(strtolower($code));
    }

    /**
     * 遊戲商 支援語系
     * @param string $code
     * @Cacheable(prefix="vendor_language", value="_#{code}", listener="vendor_language_cache")
     */
    private function languageCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
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
     */
    public function currency(string $code)
    {
        return $this->currencyCache(strtolower($code));
    }

    /**
     * 遊戲商 支援幣別
     * @param string $code
     * @Cacheable(prefix="vendor_currency", value="_#{code}", listener="vendor_currency_cache")
     */
    private function currencyCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "currency" => 1
            ]
        ]));

        if ($data) {
            $_data =  json_decode(json_encode($data['currency']), true);
            $curr = [];
            foreach ($_data as $key => $value) {
                if (!empty($value['vendor'])&&!empty($value['rate'])) {
                    $curr[strtoupper($key)] = $value['vendor'];
                }
            }
            return $curr;
        }

        return null;
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     */
    public function currencyRate(string $code) {
        return $this->currencyRateCache(strtolower($code));
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     * @Cacheable(prefix="vendor_currency_rate", value="_#{code}", listener="vendor_currency_rate_cache")
     */
    public function currencyRateCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
        $data = current($this->mongodb->fetchAll('vendors', [
            'code' => $code
        ], [
            'projection' => [
                "currency" => 1
            ]
        ]));

        if ($data) {
            $_data =  json_decode(json_encode($data['currency']), true);
            $curr = [];
            foreach ($_data as $key => $value) {
                if (!empty($value['vendor'])&&!empty($value['rate'])) {
                    $curr[$key] = floatval($value['rate']);
                }
            }
            return $curr;
        }

        return null;
    }

    /**
     * 遊戲清單
     * @param string $code
     */
    public function games(string $code) {
        return $this->gamesCache(strtolower($code));
    }

    /**
     * 遊戲清單
     * @param string $code
     * @Cacheable(prefix="vendor_games", value="_#{code}", listener="vendor_games_cache")
     */
    private function gamesCache(string $code) {
        $this->dbDefaultPool();
//        $code = strtolower($code);
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
     * @Cacheable(prefix="vendor_game", value="_#{gameCode}", listener="vendor_game_cache")
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
     */
    public function gameCodeMapping(string $vendorCode)
    {
        return $this->gameCodeMappingCache(strtolower($vendorCode));
    }

    /**
     * 遊戲代碼與ID對應
     * @param string $vendorCode
     * @Cacheable(prefix="gamecode_mapping", value="_#{vendorCode}", listener="gamecode_mapping_cache")
     */
    private function gameCodeMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "game_id" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('game_id', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲代碼與供應商遊戲代碼對應
     * @param string $vendorCode
     */
    public function vendorGameCodeMapping(string $vendorCode) {
        return $this->vendorGameCodeMappingCache(strtolower($vendorCode));
    }

    /**
     * 遊戲代碼與供應商遊戲代碼對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gamecode_mapping", value="_#{vendorCode}", listener="vendor_gamecode_mapping_cache")
     */
    private function vendorGameCodeMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "vendor_game_code" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('vendor_game_code', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲ID與代碼對應
     * @param string $vendorCode
     * @return array
     */
    public function gameIdMapping(string $vendorCode): array
    {
        return $this->gameIdMappingCache(strtolower($vendorCode));
    }

    /**
     * 遊戲ID與代碼對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gameid_mapping", value="_#{vendorCode}", listener="vendor_gameid_mapping_cache")
     */
    private function gameIdMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "game_id" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('game_code', 'game_id')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     * @return array
     */
    public function gameNameMapping(string $vendorCode): array
    {
        return $this->gameNameMappingCache(strtolower($vendorCode));
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gamename_mapping", value="_#{vendorCode}", listener="vendor_gamename_mapping_cache")
     */
    private function gameNameMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "name" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('name', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     * @return array
     */
    public function gameTypeMapping(string $vendorCode): array
    {
        return $this->gameTypeMappingCache(strtolower($vendorCode));
    }

    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_gametype_mapping", value="_#{vendorCode}", listener="vendor_gametype_mapping_cache")
     */
    private function gameTypeMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "game_type" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('game_type','game_code')->toArray();;
        }

        return [];
    }

    /**
     * GF遊戲代碼與類型對應
     * @param string $vendorCode
     * @return array
     */
    public function gfGameTypeMapping(string $vendorCode): array
    {
        return $this->gfGameTypeMappingCache(strtolower($vendorCode));
    }

    /**
     * GF遊戲代碼與類型對應
     * @param string $vendorCode
     * @Cacheable(prefix="gf_gametype_mapping", value="_#{vendorCode}", listener="gf_gametype_mapping_cache")
     */
    private function gfGameTypeMappingCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "gf_game_type" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('gf_game_type','game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲維護清單
     * @param string $vendorCode
     * @return array
     */
    public function gameMaintainList(string $vendorCode): array
    {
        return $this->gameMaintainListCache(strtolower($vendorCode));
    }

    /**
     * 遊戲維護清單
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_game_maintain_list", value="_#{vendorCode}", listener="vendor_game_maintain_list_cache")
     */
    private function gameMaintainListCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode,
            'status' => [
                '$ne' => 'online'
            ]
        ], [
            'projection' => [
                "game_code" => 1,
                "game_id" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('game_id', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲運作清單
     * @param string $vendorCode
     * @return array
     */
    public function gameWorkingList(string $vendorCode): array
    {
        return $this->gameWorkingListCache(strtolower($vendorCode));
    }

    /**
     * 遊戲運作清單
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_game_working_list", value="_#{vendorCode}", listener="vendor_game_working_list_cache")
     */
    private function gameWorkingListCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode,
            'status' => 'online'
        ], [
            'projection' => [
                "game_code" => 1,
                "game_id" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('game_id', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 遊戲狀態對照表
     * @param string $vendorCode
     * @return array
     */
    public function gameStatusList(string $vendorCode): array
    {
        return $this->gameStatusListCache(strtolower($vendorCode));
    }

    /**
     * 遊戲狀態對照表
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_game_status_list", value="_#{vendorCode}", listener="vendor_game_status_list_cache")
     */
    private function gameStatusListCache(string $vendorCode) {
        $this->dbDefaultPool();
//        $vendorCode = strtolower($vendorCode);
        $data = $this->mongodb->fetchAll('games', [
            'vendor_code' => $vendorCode
        ], [
            'projection' => [
                "game_code" => 1,
                "status" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('status', 'game_code')->toArray();;
        }

        return [];
    }

    /**
     * 錢包代碼
     * @Cacheable(prefix="vendor_wallet_code", listener="vendor_wallet_code_cache")
     */
    public function walletCodes() {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('vendors');
        if ($data) {
            return collect($data)->pluck('wallet_code')->toArray();
        }
        return [];
    }

    /**
     * 遊戲商線路群組狀態
     * @param string $vendorCode
     * @return array
     */
    public function channelGroupStatus(string $vendorCode): array
    {
        return $this->channelGroupStatusCache(strtolower($vendorCode));
    }

    /**
     * 遊戲商線路群組狀態
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_channel_group_status", value="_#{vendorCode}", listener="vendor_channel_group_status_cache")
     */
    private function channelGroupStatusCache(string $vendorCode) {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('vendor_channel', [
            'code' => $vendorCode,
        ], [
            'projection' => [
                "name" => 1,
                "status" => 1
            ]
        ]);

        if ($data) {
            return collect($data)->pluck('status', 'name')->toArray();
        }
        return [];
    }
}
