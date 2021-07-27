<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;


use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 玩家Session
 * Class PlayerSessionCacheService
 * @package GiocoPlus\PrismPlus\Service
 */
class PlayerSessionCacheService
{

    /**
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

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
     * 建立玩家session
     * @param string $key
     * @param array $params
     * @return string|null
     */
    public function create(string $key, array $params = []) {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        $sessionStr = implode("::", $params);
        $sessionStr = base64url_encode($sessionStr);
        $redis->set($key, $sessionStr);
        return $sessionStr;
    }

    /**
     * 清除玩家快取
     * @param string $key
     * @return bool
     */
    public function clear(string $key) {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        return $redis->del($key) > 0;
    }

}