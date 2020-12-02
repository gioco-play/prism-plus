<?php

declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Repository;

use Hyperf\Di\Annotation\Inject;
use GiocoPlus\Mongodb\MongoDbConst;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use GiocoPlus\EZAdmin\Service\CacheService;
use GiocoPlus\Mongodb\MongoDb;

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
     * 選擇商戶資料庫
     * @param string $code
     * @param string|null $dbName
     * @param string $readPref
     * @return MongoDb
     */
    public function setOperator(string $code, string $dbName = null, string $readPref = MongoDbConst::ReadPrefPrimary) {
        $dbName = $dbName ?? "{$code}_db";
        $op = $this->cache->operator($code);
        $dbConn = $op['db']->mongodb;
        $dbCfg = mongodb_pool_config(
            $dbConn->host,
            $dbConn->db_name??$dbName,
            intval($dbConn->port),
            $dbConn->replica,
            $dbConn->read_preference??$readPref);
        $config = ApplicationCont::getContainer()->get(ConfigInterface::class);
        $config->set("mongodb.db_{$code}", $dbCfg);
        return $this->mongodb->setPool("db_{$code}");
    }
}