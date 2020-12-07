<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Repository;

use GiocoPlus\PrismPlus\Service\CacheService;
use GiocoPlus\Mongodb\MongoDb;
use GiocoPlus\Mongodb\MongoDbConst;
use Hyperf\Cache\Cache;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * 資料庫管理
 */
class DbManager
{

    /**
     * @Inject
     * @var CacheService
     */
    protected $cache;

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
        try {
            $dbName = $dbName ?? "{$code}_db";
            $op = $this->cache->operator($code);
            $dbConn = $op['db']->mongodb;
            $dbCfg = mongodb_pool_config(
                $dbConn->host,
                $dbConn->db_name??$dbName,
                intval($dbConn->port),
                $dbConn->replica,
                $dbConn->read_preference??$readPref);
            $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
            $config->set("mongodb.db_{$code}", $dbCfg);
            return $this->mongodb->setPool("db_{$code}");
        } catch (\RuntimeException $e) {
            echo sprintf('RuntimeException %s[%s] in %s', $e->getMessage(), $e->getLine(), $e->getFile());
        }
    }

    /**
     * 選擇商戶PostgreSql資料庫
     * @param string $code
     * @param string|null $dbName
     * @return \Swoole\Coroutine\PostgreSQL|void
     */
    public function opPostgreDb(string $code, string $dbName = null) {
        try {
            $op = $this->cache->operator($code);
            $dbConn = $op['db']->postgres;
            //
            $host = $dbConn->host;
            $port = $dbConn->port;
            $user = $dbConn->user;
            $password = $password ?? $dbConn->password;
            $dbName = $dbName ?? "{$code}_db";
            //
            $pg = new \Swoole\Coroutine\PostgreSQL();
            $conn = $pg->connect("host={$host} port={$port} dbname={$dbName} user={$user} password={$password}");
            if (!$conn) {
                var_dump($pg->error);
                return;
            }
            return $pg;
        } catch (\RuntimeException $e) {
            echo sprintf('RuntimeException %s[%s] in %s', $e->getMessage(), $e->getLine(), $e->getFile());
        }
    }
}