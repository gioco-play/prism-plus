<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;


use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
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
     * 營運商基本資料
     * @param string $code
     * @return bool
     */
    public function basic(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_basic_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }
    /**
     * 總開關
     * @param string $code
     * @return bool
     */
    public function mainSwitch(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_main_switch_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }
    /**
     * 遊戲商 開關 / 配置
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function vendorSetting(string $code, string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_vendor_setting_cache', [
            'code' => strtoupper($code),
            'vendorCode' => strtolower($vendorCode)
        ]));
        return true;
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @return bool
     */
    public function currencyRate(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_currency_rate_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }
    
    /**
     * 營運商幣別對應
     * @param string $code
     * @return bool
     */
    public function currency(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_currency_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }
    
    /**
     * 運營商 封鎖遊戲
     * @param string $code
     * @param string $vendorCode
     * @return bool
     */
    public function blockGames(string $code, string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_block_game_cache', [
            'code' => strtoupper($code),
            'vendorCode' => strtolower($vendorCode)
        ]));
        return true;
    }

    /**
     * 運營商 API 白名單
     * @param string $code
     * @return bool
     */
    public function apiWhitelist(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_api_whitelist_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
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
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_seamless_setting_cache', [
            'code' => strtoupper($code)
        ]));
        return true;
    }

    /**
     * 營商遊戲拉單開關
     * @param string $vendorCode
     * @return bool
     */
    public function grabberLogEnable(string $vendorCode)
    {
        $this->dispatcher->dispatch(new DeleteListenerEvent('grabber_log_enable_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));
        return true;
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
