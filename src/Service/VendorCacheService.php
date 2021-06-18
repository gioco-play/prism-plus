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
     * @Cacheable(prefix="vendor_basic", value="_#{code}", listener="vendor_basic_cache")
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
                "account_delimiter" => 1,
                "wallet_type" => 1
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
     * @Cacheable(prefix="vendor_request_params", value="_#{code}", listener="vendor_request_params_cache")
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
     * @Cacheable(prefix="vendor_betlog_field", value="_#{code}", listener="vendor_betlog_field_cache")
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
            return json_decode(json_encode($data['betlog_field']), true);
        }

        return null;
    }

    /**
     * 遊戲商 IP白名單
     * @param string $code
     * @Cacheable(prefix="vendor_ip_whitelist", value="_#{code}", listener="vendor_ip_whitelist_cache")
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
     * @Cacheable(prefix="vendor_language", value="_#{code}", listener="vendor_language_cache")
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
     * @Cacheable(prefix="vendor_currency", value="_#{code}", listener="vendor_currency_cache")
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
            $_data =  json_decode(json_encode($data['currency']), true);
            $curr = [];
            foreach ($_data as $key => $value) {
                if (!empty($value['vendor'])&&!empty($value['rate'])) {
                    $curr[strtoupper($key)] = strtoupper($value['vendor']);
                }
            }
            return $curr;
        }

        return null;
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     * @Cacheable(prefix="vendor_currency_rate", value="_#{code}", listener="vendor_currency_rate_cache")
     */
    public function currencyRate(string $code) {
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
     * @Cacheable(prefix="vendor_games", value="_#{code}", listener="vendor_games_cache")
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
     * @Cacheable(prefix="gamecode_mapping", value="_#{vendorCode}", listener="gamecode_mapping_cache")
     */
    public function gameCodeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_gamecode_mapping", value="_#{vendorCode}", listener="vendor_gamecode_mapping_cache")
     */
    public function vendorGameCodeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_gameid_mapping", value="_#{vendorCode}", listener="vendor_gameid_mapping_cache")
     */
    public function gameIdMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_gamename_mapping", value="_#{vendorCode}", listener="vendor_gamename_mapping_cache")
     */
    public function gameNameMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_gametype_mapping", value="_#{vendorCode}", listener="vendor_gametype_mapping_cache")
     */
    public function gameTypeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="gf_gametype_mapping", value="_#{vendorCode}", listener="gf_gametype_mapping_cache")
     */
    public function gfGameTypeMapping(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_game_maintain_list", value="_#{vendorCode}", listener="vendor_game_maintain_list_cache")
     */
    public function gameMaintainList(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_game_working_list", value="_#{vendorCode}", listener="vendor_game_working_list_cache")
     */
    public function gameWorkingList(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
     * @Cacheable(prefix="vendor_game_status_list", value="_#{vendorCode}", listener="vendor_game_status_list_cache")
     */
    public function gameStatusList(string $vendorCode) {
        $this->dbDefaultPool();
        $vendorCode = strtolower($vendorCode);
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
}
