<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;


use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
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
     * 建立玩家Session
     * @param string $operatorCode
     * @param string $playerName
     * @param $game
     * @param string $currency
     * @Cacheable(prefix="pg_session", value="_#{operatorCode}_#{playerName}", listener="player_session_cache")
     */
    public function createPlayerSession(string $operatorCode, string $playerName, $game, string $currency) {
        return base64url_encode("{$playerName}_{$operatorCode}::{$game['game_id']}::{$game['game_code']}::{$currency}");
    }

    /**
     * 清除玩家快取
     * @param string $operatorCode
     * @param string $playerName
     * @return bool
     */
    public function clearPlayerSession(string $operatorCode, string $playerName) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('player_session_cache', [
            'operatorCode' => $operatorCode,
            'playerName' => $playerName
        ]));

        return true;
    }

}