<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Repository;

use GiocoPlus\Mongodb\MongoDb;
use GiocoPlus\Mongodb\MongoDbConst;
use GiocoPlus\PrismPlus\Helper\Log;
use GiocoPlus\PrismPlus\Service\OperatorCacheService;
use Hyperf\Cache\Cache;
use Hyperf\Contract\ConfigInterface;
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
