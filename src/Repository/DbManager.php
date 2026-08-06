<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Repository;

use GiocoPlus\Mongodb\MongoDb;
use GiocoPlus\Mongodb\MongoDbConst;
use GiocoPlus\PrismPlus\Helper\Log;
use GiocoPlus\PrismPlus\Service\OperatorCacheService;
use Hyperf\Cache\Cache;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Di\Annotation\Inject;

/**
 * 資料庫管理
 */
class DbManager
{

    /**
     * @Inject
     * @var OperatorCacheService
     */
    protected $opCache;

    /**
     * @Inject
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * 選擇商戶 MongoDb 資料庫
     * @param string $code
     * @param string|null $dbName
     * @param string $readPref
     * @return MongoDb
     */
    public function opMongoDb(string $code, string $dbName = null, string $readPref = MongoDbConst::ReadPrefPrimary) {
        $dbName = strtolower($dbName ?? "{$code}_db");
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        if (!$config->has("mongodb.db_{$code}")) {
            $op = $this->opCache->dbSetting($code);
//            if (!isset($op->mongodb)) {
//                $op = $this->getDbSetting($code);
//            }
            if (!isset($op->mongodb)) {
                throw new \Exception("[{$code}] MongoDb 資料庫未配置");
            }
            $dbConn = $op->mongodb;
            $dbCfg = mongodb_pool_config(
                $dbConn->host,
                $dbConn->db_name ?? $dbName,
                intval($dbConn->port),
                $dbConn->replica,
                $dbConn->read_preference ?? $readPref,
                [
                    'database' => 'admin'
                ],
                '',
                '',
                200
            );
            $config->set("mongodb.db_{$code}", $dbCfg);
        }
        return $this->mongodb->setPool("db_{$code}");
    }

    /**
     * 選擇商戶 MongoDb 報表資料庫
     * @param string $code
     * @param string|null $dbName
     * @param string $readPref
     * @return MongoDb
     */
    public function opMongoDbRep(string $code, string $dbName = null, string $readPref = MongoDbConst::ReadPrefPrimary) {
        $dbName = strtolower($dbName ?? "{$code}_db");
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        if (!$config->has("mongodb.db_{$code}_rep")) {
            $op = $this->opCache->dbSetting($code);
//            if (!isset($op->mongodb_rep)) {
//                $op = $this->getDbSetting($code);
//            }
            if (!isset($op->mongodb_rep)) {
                throw new \Exception("[{$code}] MongoDb 報表資料庫未配置");
            }
            $dbConn = $op->mongodb_rep;

            $dbCfg = mongodb_pool_config(
                $dbConn->host,
                $dbConn->db_name ?? $dbName,
                intval($dbConn->port),
                $dbConn->replica,
                $dbConn->read_preference ?? $readPref,
                [
                    'database' => 'admin'
                ],
                '',
                '',
                200
            );
            $config->set("mongodb.db_{$code}_rep", $dbCfg);
        }
        return $this->mongodb->setPool("db_{$code}_rep");
    }

    /**
     * 選擇商戶 PostgreSql 資料庫
     *
     * @param string $code
     * @param string|null $dbName
     * @return \Swoole\Coroutine\PostgreSQL|void
     */
    public function opPostgreDb(string $code, string $dbName = null) {
        $op = $this->opCache->dbSetting($code);
        // if (!isset($op->postgres)){
        //     $op = $this->getDbSetting($code);
        // }

        if (!isset($op->postgres)) {
            throw new \Exception("[{$code}] Postgres 資料庫未配置");
        }

        $st = micro_timestamp();

        $dbConn = $op->postgres;
        $host = $dbConn->host;
        $port = $dbConn->port;
        $user = $dbConn->user;
        $password = $dbConn->password;
        $dbName = $dbName ?? strtolower("{$code}_db");
        //
        $pg = new \Swoole\Coroutine\PostgreSQL();
        $pgConnect = "host={$host} port={$port} dbname={$dbName} user={$user} password={$password} connect_timeout=3";
        $maxRetries = 2;
        $retryDelaySeconds = 0.2;
        $maxAttempts = $maxRetries + 1;
        $conn = false;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $pg = new \Swoole\Coroutine\PostgreSQL();
            $conn = $pg->connect($pgConnect);
            if ($conn) {
                break;
            }

            if ($attempt < $maxAttempts) {
                // In coroutine workers use non-blocking sleep; fallback for non-coroutine contexts.
                if (\Swoole\Coroutine::getCid() > 0) {
                    \Swoole\Coroutine::sleep($retryDelaySeconds);
                } else {
                    usleep((int) ($retryDelaySeconds * 1000000));
                }
            }
        }
        if (!$conn) {
            $execTime = ((micro_timestamp() - $st) / 1000);
            // Log::internalError(__FUNCTION__ . " pg conn fail [{$code}]", [
            //     "exec_time" => ((micro_timestamp() - $st) / 1000),
            //     "message" => $pg->error,
            //     "attempts" => $maxAttempts,
            // ]);
            throw new \Exception("[{$code}] Postgres 未連線成功 message: {$pg->error} attempts: {$maxAttempts} exec_time: {$execTime}");
        }
        return $pg;
    }

    /**
     * Grabber MongoDb 的 Consul KV key
     */
    const GRABBER_CONSUL_KEY = 'grabber_mongo';

    /**
     * 選擇 Grabber MongoDb 資料庫
     *
     * 連線資訊取自 Consul KV grabber_mongo，內容為 JSON。
     * host 與 db_name 為必填字串；port 為 1-65535 的整數，預設 27017。
     * read_preference 預設 primary，可用值為 primary、primaryPreferred、secondary、secondaryPreferred、nearest。
     * 例如 {"host":"mongo","db_name":"grabber_prod","port":27017,"replica":"rs0","user":"user","password":"password","read_preference":"primary"}。
     * 亦支援多節點 replica set，host 以逗號分隔各節點（各節點自帶 port），replica set 名稱可用 replicaSet 或 replica 欄位。
     * 例如 {"host":"h1:27017,h2:27017,h3:27017","db_name":"grabber_prod","replicaSet":"rs0"}。
     * 設定在 worker 第一次建立 pool 後固定，更新 Consul KV 後需重啟 worker 才會生效。
     *
     * @return MongoDb
     * @throws \Exception
     */
    public function grabberDb(): MongoDb {
        $key = self::GRABBER_CONSUL_KEY;
        $pool = "db_{$key}";
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        if (!$config->has("mongodb.{$pool}")) {
            $dbConn = json_decode($this->consulKV($key), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($dbConn)) {
                throw new \Exception("Consul KV [{$key}] JSON 格式錯誤");
            }
            foreach (['host', 'db_name'] as $field) {
                if (!isset($dbConn[$field]) || !is_string($dbConn[$field]) || trim($dbConn[$field]) === '') {
                    throw new \Exception("Consul KV [{$key}] {$field} 必須為非空字串");
                }
            }
            if (isset($dbConn['port']) && (!is_int($dbConn['port']) || $dbConn['port'] < 1 || $dbConn['port'] > 65535)) {
                throw new \Exception("Consul KV [{$key}] port 必須為 1-65535 的整數");
            }
            foreach (['replica', 'replicaSet', 'user', 'password'] as $field) {
                if (isset($dbConn[$field]) && !is_string($dbConn[$field])) {
                    throw new \Exception("Consul KV [{$key}] {$field} 必須為字串");
                }
            }
            $readPref = $dbConn['read_preference'] ?? MongoDbConst::ReadPrefPrimary;
            if (!is_string($readPref) || !in_array($readPref, [
                MongoDbConst::ReadPrefPrimary,
                MongoDbConst::ReadPrefPrimaryPreferred,
                MongoDbConst::ReadPrefSecondary,
                MongoDbConst::ReadPrefSecondaryPreferred,
                MongoDbConst::ReadPrefNearest,
            ], true)) {
                throw new \Exception("Consul KV [{$key}] read_preference 無效");
            }
            $dbCfg = mongodb_pool_config(
                str_replace(',', ';', $dbConn['host']),
                $dbConn['db_name'],
                $dbConn['port'] ?? 27017,
                $dbConn['replicaSet'] ?? $dbConn['replica'] ?? '',
                $readPref,
                [
                    'database' => 'admin'
                ],
                $dbConn['user'] ?? '',
                $dbConn['password'] ?? '',
                200
            );
            $config->set("mongodb.{$pool}", $dbCfg);
        }
        return $this->mongodb->setPool($pool);
    }

    /**
     * 讀取 Consul KV 的值
     *
     * Consul 位址取自 env('CONSUL_HOST')，預設 http://127.0.0.1:8500
     *
     * @param string $key KV 的 key，例如 grabber/db
     * @return string
     * @throws \Exception
     */
    public function consulKV(string $key): string
    {
        $client = ApplicationContext::getContainer()->get(ClientFactory::class)->create([
            'base_uri' => env('CONSUL_HOST', 'http://127.0.0.1:8500'),
            'timeout' => 3,
            'http_errors' => false,
        ]);

        $response = $client->get('/v1/kv/' . $key, [
            'query' => ['raw' => 'true'],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Consul KV [{$key}] 讀取失敗 status: {$response->getStatusCode()}");
        }

        return (string) $response->getBody();
    }

    /**
     * 取得DB配置
     * @param string $code
     */
    private function getDbSetting(string $code) {
        $_mongodb = ApplicationContext::getContainer()->get(MongoDb::class);
        $_mongodb->setPool('default');
        $data = current($_mongodb->fetchAll('operators', [
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
     * 取得Postgres連線資訊
     * @param string $code
     * @return mixed
     * @throws \Exception
     */
    public function opPostgresDSN(string $code)
    {
        $op = $this->opCache->dbSetting($code);
        if (isset($op->postgres)) {
            $op = $this->getDbSetting($code);
        }
        if (!isset($op->postgres)) {
            throw new \Exception("[{$code}] Postgres 資料庫未配置");
        }
        return $op->postgres;
    }
}
