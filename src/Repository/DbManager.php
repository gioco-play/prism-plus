<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Repository;

use GiocoPlus\Mongodb\MongoDb;
use GiocoPlus\Mongodb\MongoDbConst;
use GiocoPlus\PrismPlus\Service\OperatorCacheService;
use Hyperf\Cache\Cache;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * 資料庫管理
 */
class DbManager
{

    /**
     * @Inject()
     * @var OperatorCacheService
     */
    protected $opCache;

    /**
     * @Inject
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * 選擇商戶MongoDb資料庫
     * @param string $code
     * @param string|null $dbName
     * @param string $readPref
     * @return MongoDb
     */
    public function opMongoDb(string $code, string $dbName = null, string $readPref = MongoDbConst::ReadPrefPrimary) {
        $dbName = strtolower($dbName ?? "{$code}_db");
        $op = $this->opCache->dbSetting($code);
        if (!isset($op->mongodb)) {
            $op = $this->getDbSetting($code);
        }
        if (!isset($op->mongodb)) {
            throw new \Exception("[{$code}] 資料庫未配置");
        }
        $dbConn = $op->mongodb;
        $dbCfg = mongodb_pool_config(
            $dbConn->host,
            $dbConn->db_name??$dbName,
            intval($dbConn->port),
            $dbConn->replica,
            $dbConn->read_preference??$readPref);
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        if (!$config->has("mongodb.db_{$code}")) {
            $config->set("mongodb.db_{$code}", $dbCfg);
        }
        return $this->mongodb->setPool("db_{$code}");
    }

    /**
     * 選擇商戶PostgreSql資料庫
     * 
     * @param string $code
     * @param string|null $dbName
     * @return \Swoole\Coroutine\PostgreSQL|void
     */
    public function opPostgreDb(string $code, string $dbName = null) {
        $op = $this->opCache->dbSetting($code);
        if (isset($op->postgres)){
            $op = $this->getDbSetting($code);
        }
        if (!isset($op->postgres)) {
            throw new \Exception("[{$code}] MongoDb 資料庫未配置");
        }
        $dbConn = $op->postgres;
        $host = $dbConn->host;
        $port = $dbConn->port;
        $user = $dbConn->user;
        $password = $dbConn->password;
        $dbName = $dbName ?? strtolower("{$code}_db");
        //
        $pg = new \Swoole\Coroutine\PostgreSQL();
        $conn = $pg->connect("host={$host} port={$port} dbname={$dbName} user={$user} password={$password}");
        if (!$conn) {
            var_dump($pg->error);
            return;
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
}