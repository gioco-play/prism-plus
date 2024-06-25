<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
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

    /**
     * @Inject()
     * @var RedisFactory
     */
    protected $redisFactory;


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
     * @throws \Exception
     */
    public function basic(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_basic_' . $code;

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');

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
                    "wallet_type" => 1,
                    "support" => 1
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
     * 遊戲商 線路參數
     * @param string $code
     * @throws \Exception
     */
    public function requestParams(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_request_params_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');

        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = current($this->mongodb->fetchAll('vendors', [
                'code' => $code
            ], [
                'projection' => [
                    "request_params" => 1
                ]
            ]));

            if (isset($data['request_params'])) {
                $redisData = json_encode($data['request_params']);
                $redis->setex($key, 60*60*1, $redisData);
                return $data['request_params'];
            }

            return null;
        }
        return json_decode($redis->get($key), true);
    }

    /**
     * 遊戲商 投注紀錄欄位
     * @param string $code
     */
    public function betlogField(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_betlog_field_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');

        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = current($this->mongodb->fetchAll('vendors', [
                'code' => $code
            ], [
                'projection' => [
                    "betlog_field" => 1
                ]
            ]));

            if (isset($data['betlog_field'])) {
                $redisData = json_encode($data['betlog_field']);
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲商 IP 白名單
     * @param string $code
     * @throws \Exception
     */
    public function ipWhitelist(string $code) {
        $code = strtolower($code);
        $key = 'vendor_ip_whitelist_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = current($this->mongodb->fetchAll('vendors', [
                'code' => $code
            ], [
                'projection' => [
                    "filter_ip" => 1,
                    "ip_whitelist" => 1
                ]
            ]));

            if ($data) {
                $redis->setex($key, 60*60*1, json_encode($data));
                return $data;
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲商 支援語系
     * @param string $code
     * @throws \Exception
     */
    public function language(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_language_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = current($this->mongodb->fetchAll('vendors', [
                'code' => $code
            ], [
                'projection' => [
                    "language" => 1
                ]
            ]));

            if (isset($data['language'])) {
                $redisData = json_encode($data['language']);
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲商 支援幣別
     * @param string $code
     * @throws \Exception
     */
    public function currency(string $code)
    {
        $code = strtolower($code);
        $key = 'vendor_currency_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
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
                $redisData = json_encode($curr);
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     * @throws \Exception
     */
    public function currencyRate(string $code) {
        $code = strtolower($code);
        $key = 'vendor_currency_rate_' . $code;

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
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
                    if (!empty($value['vendor']) && !empty($value['rate'])) {
                        $curr[$key] = floatval($value['rate']);
                    }
                }
                $redisData = json_encode($curr);
                $redis->setex($key, 60*60*1, $redisData);
                return $curr;
            }
            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲清單
     * @param string $code
     * @throws \Exception
     */
    public function games(string $code) {
        $code = strtolower($code);
        $key = 'vendor_games_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', ['vendor_code' => $code], [
                'projection' => [
                    "updated_time" => 0,
                    "created_time" => 0
                ]
            ]);

            if ($data) {
                $redis->setex($key, 60*60*1, json_encode($data));
                return $data;
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 單一遊戲資訊
     * @param string $gameCode
     * @throws \Exception
     */
    public function game(string $gameCode) {
        $gameCode = strtolower($gameCode);
        $key = 'vendor_game_' . $gameCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $gameCode = strtolower($gameCode);
            $data = current($this->mongodb->fetchAll('games', ['game_code' => $gameCode], [
                'projection' => [
                    "updated_time" => 0,
                    "created_time" => 0
                ]
            ]));

            if ($data) {
                $redisData = json_encode($data);
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return null;
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲代碼與ID對應
     * @param string $vendorCode
     * @throws \Exception
     */
    public function gameCodeMapping(string $vendorCode)
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'gamecode_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "game_id" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('game_id', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲代碼與供應商遊戲代碼對應
     * @param string $vendorCode
     * @throws \Exception
     */
    public function vendorGameCodeMapping(string $vendorCode) {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gamecode_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "vendor_game_code" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('vendor_game_code', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }


    /**
     * 遊戲 ID 與代碼對應
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gameIdMapping(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gameid_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "game_id" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('game_code', 'game_id')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }
            return [];
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gameNameMapping(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gamename_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "name" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('name', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }


    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gameTypeMapping(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_gametype_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "game_type" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('game_type','game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }


    /**
     * GF遊戲代碼與類型對應
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gfGameTypeMapping(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'gf_gametype_mapping_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "gf_game_type" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('gf_game_type','game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }


    /**
     * 遊戲維護清單
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gameMaintainList(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_maintain_list_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
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
                $redisData = json_encode(collect($data)->pluck('game_id', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲運作清單
     * @param string $vendorCode
     * @return array
     * @throws \Exception
     */
    public function gameWorkingList(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_working_list_' . $vendorCode;

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
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
                $redisData = json_encode(collect($data)->pluck('game_id', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }


    /**
     * 遊戲狀態對照表
     * @param string $vendorCode
     * @return array
     */
    public function gameStatusList(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_game_status_list_' . $vendorCode;

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('games', [
                'vendor_code' => $vendorCode
            ], [
                'projection' => [
                    "game_code" => 1,
                    "status" => 1
                ]
            ]);

            if ($data) {
                $redisData = json_encode(collect($data)->pluck('status', 'game_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }

            return [];
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 錢包代碼
     * @throws \Exception
     */
    public function walletCodes() {
        $key = 'vendor_wallet_code';

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
            $this->dbDefaultPool();
            $data = $this->mongodb->fetchAll('vendors');
            if ($data) {
                $redisData = json_encode(collect($data)->pluck('wallet_code')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }
            return [];
        }
        return json_decode($redis->get($key),true);
    }

    /**
     * 遊戲商線路群組狀態
     * @param string $vendorCode
     * @return array
     */
    public function channelGroupStatus(string $vendorCode): array
    {
        $vendorCode = strtolower($vendorCode);
        $key = 'vendor_channel_group_status_' . $vendorCode;

//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redis = $this->redisFactory->get('default');
        if (!$redis->get($key)) {
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
                $redisData = json_encode(collect($data)->pluck('status', 'name')->toArray());
                $redis->setex($key, 60*60*1, $redisData);
                return json_decode($redisData, true);
            }
            return [];
        }
        return json_decode($redis->get($key),true);
    }
}
