<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;


use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 遊戲商清除快取
 * Class VendorCacheFlushService
 * @package GiocoPlus\PrismPlus\Service
 */
class VendorCacheFlushService
{

    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * 基本資料
     * @param string $code
     * @return bool
     */
    public function basic(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_basic_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 請求參數
     * @param string $code
     * @return bool
     */
    public function requestParams(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_request_params_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 投注紀錄欄位
     * @param string $code
     * @return bool
     */
    public function betlogField(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_betlog_field_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 IP白名單
     * @param string $code
     * @return bool
     */
    public function ipWhitelist(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_ip_whitelist_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 支援語系
     * @param string $code
     * @return bool
     */
    public function language(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_language_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 支援幣別
     * @param string $code
     * @return bool
     */
    public function currency(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_currency_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲商 支援幣值
     * @param string $code
     * @return bool
     */
    public function currencyRate(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_currency_rate_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲清單
     * @param string $code
     * @return bool
     */
    public function games(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_games_cache', [
            'code' => strtolower($code)
        ]));

        return true;
    }

    /**
     * 遊戲
     * @param string $gameCode
     * @return bool
     */
    public function game(string $gameCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_game_cache', [
            'gameCode' => $gameCode
        ]));

        return true;
    }

    /**
     * 遊戲代碼與ID對應
     * @param string $vendorCode
     * @return bool
     */
    public function gameCodeMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('gamecode_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲代碼與供應商遊戲代碼對應
     * @param string $vendorCode
     * @return bool
     */
    public function vendorGameCodeMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_gamecode_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲ID與代碼對應
     * @param string $vendorCode
     * @return bool
     */
    public function gameIdMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_gameid_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲代碼與名稱對應
     * @param string $vendorCode
     */
    public function gameNameMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_gamename_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲代碼與類型對應
     * @param string $vendorCode
     */
    public function gameTypeMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_gametype_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * GF遊戲代碼與類型對應
     * @param string $vendorCode
     */
    public function gfGameTypeMapping(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('gf_gametype_mapping_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲維護清單
     * @param string $vendorCode
     */
    public function gameMaintainList(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_game_maintain_list_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲運作清單
     * @param string $vendorCode
     */
    public function gameWorkingList(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_game_working_list_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 遊戲狀態對照表
     * @param string $vendorCode
     */
    public function gameStatusList(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_game_status_list_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }

    /**
     * 錢包代碼
     * @return bool
     */
    public function walletCodes() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_wallet_code_cache', [
        ]));

        return true;
    }

    /**
     * 遊戲商線路群組狀態
     * @param string $vendorCode
     * @return bool
     */
    public function channelGroupStatus(string $vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor_channel_group_status_cache', [
            'vendorCode' => strtolower($vendorCode)
        ]));

        return true;
    }
}