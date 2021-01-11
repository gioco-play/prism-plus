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
     * @param string $key
     * @param string|null $operatorCode
     * @param string|null $playerName
     * @param string|null $currency
     * @param null $game
     * @return string
     * @Cacheable(prefix="player_session", value="_#{key}", listener="player_session_cache")
     */
    public function create(string $key, string $operatorCode = null, string $playerName = null, string $currency = null, $game = null) {
        if (empty($operatorCode)) {
            return null;
        }
        return base64url_encode("{$playerName}_{$operatorCode}::{$currency}::{$game['game_id']}::{$game['game_code']}::{$game['game_type']}");
    }

    /**
     * 清除玩家快取
     * @param string $key
     * @return bool
     */
    public function clear(string $key) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('player_session_cache', [
            'key' => $key
        ]));

        return true;
    }

}