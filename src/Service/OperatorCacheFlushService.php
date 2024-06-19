<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;


use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;
/**
 * 運營商清除快取
 * Class CacheFlushService
 * @package GiocoPlus\PrismPlus\Service
 */
class OperatorCacheFlushService
{

    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @Inject()
     * @var RedisFactory
     */
    protected $redisFactory;

    /**
     * 營運商基本資料
     * @param string $code
     * @return bool
     * @throws \Exception
     */
    public function basic(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_basic_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_basic_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 總開關
     * @param string $code
     * @return bool
     * @throws \Exception
     */
    public function mainSwitch(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_main_switch_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_main_switch_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }
    /**
     * 遊戲商 開關 / 配置
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function vendorSetting(string $code, string $vendorCode) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_vendor_setting_cache', [
//            'code' => strtoupper($code),
//            'vendorCode' => strtolower($vendorCode)
//        ]));
//        return true;

        $code = strtoupper($code);
        $vendorCode = strtolower($vendorCode);
        $key = 'op_vendor_setting_' . $code . '_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @return bool
     */
    public function currencyRate(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_currency_rate_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_currency_rate_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }
    
    /**
     * 營運商幣別對應
     * @param string $code
     * @return bool
     */
    public function currency(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_currency_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_currency_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }
    
    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function blockGames(string $code, string $vendorCode) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_block_game_cache', [
//            'code' => strtoupper($code),
//            'vendorCode' => strtolower($vendorCode)
//        ]));
//        return true;

        $code = strtoupper($code);
        $vendorCode = strtolower($vendorCode);
        $key = 'op_block_game_' . $code . '_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return bool
     */
    public function apiWhitelist(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_api_whitelist_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_api_whitelist_' . $code;
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 運營商 DB 配置
     * @param string $code
     * @return bool
     */
    public function dbSetting(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_db_setting_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }

   /**
    * 運營商 k8s隸屬 配置
    *
    * @param string $code
    * @return bool
    */
    public function k8sSetting(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_k8s_setting_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }

    /**
     * 運營商 類單一錢包配置
     * @param string $code
     * @return bool
     */
    public function seamlessSetting(string $code) {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('op_seamless_setting_cache', [
//            'code' => strtoupper($code)
//        ]));
//        return true;

        $code = strtoupper($code);
        $key = 'op_seamless_setting_' . $code;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 營商遊戲拉單開關
     * @param string $vendorCode
     * @return bool
     */
    public function grabberLogEnable(string $vendorCode)
    {
//        $this->dispatcher->dispatch(new DeleteListenerEvent('grabber_log_enable_cache', [
//            'vendorCode' => strtolower($vendorCode)
//        ]));
//        return true;

        $vendorCode = strtolower($vendorCode);
        $key = 'grabber_log_enable_' . $vendorCode;
//        if (! ApplicationContext::getContainer()->has(Redis::class)){
//            throw new \Exception('Please make sure if there is "Redis" in the container');
//        }
//        $redis = ApplicationContext::getContainer()->get(Redis::class);

        $redisV3 = $this->redisFactory->get('v3');
        $redis = $this->redisFactory->get('default');

        var_dump($redisV3->del($key));

        return $redis->del($key) && $redisV3->del($key);
    }

    /**
     * 檢查遊戲類型
     * @param string $code
     * @param string $gameType
     */
    public function checkGameType(string $code, string $gameType)
    {
        $this->dispatcher->dispatch(new DeleteListenerEvent('check_gametype_cache', [
            'code' => strtoupper($code),
            'gameType' => $gameType
        ]));
        return true;
    }
}
