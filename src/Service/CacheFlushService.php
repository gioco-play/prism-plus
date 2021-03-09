<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
/**
 * 清除快取
 * Class CacheFlushService
 * @package GiocoPlus\PrismPlus\Service
 */
class CacheFlushService
{

    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * 角色清單
     * @return bool
     */
    public function adminRoles(){
        $this->dispatcher->dispatch(new DeleteListenerEvent('admin_user_roles_cache', [
        ]));

        return true;
    }

    /**
     * 管理者基本資料
     * @param string $account
     * @return bool
     */
    public function adminUserInfo(string $account) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('admin_user_info_cache', [
            'account' => $account
        ]));

        return true;
    }

    /**
     * 管理者帳號
     * @param string $uid
     * @return bool
     */
    public function adminUser(string $uid) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('admin_user_info_cache', [
            'uid' => $uid
        ]));

        return true;
    }

    /**
     * 公司
     * @param string $code
     * @return bool
     */
    public function company(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('company_cache', [
            'code' => $code
        ]));

        return true;
    }

    /**
     * 營運商 - 公司
     * @param string $code
     * @return bool
     */
    public function companyOpCodes(string $code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp_opcodes_cache', [
            'code' => $code
        ]));

        return true;
    }

    /**
     * 營運商 - 幣別
     * @param string $code
     * @param string $currency
     * @return bool
     */
    public function companyOpCurrency(string $code, string $currency) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp_op_currency_cache', [
            'code' => $code,
            'currency' => $currency
        ]));

        return true;
    }

    /**
     * 角色選單
     * @param string $role
     * @return bool
     */
    public function roleMenu(string $role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role_menu_cache', [
            'role' => $role
        ]));

        return true;
    }

    /**
     * 角色選單權限
     * @param string $role
     * @return bool
     */
    public function roleMenuPermits(string $role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role_menu_permits_cache', [
            'role' => $role
        ]));

        return true;
    }

    /**
     * 查詢會員資料
     * @param string $accountOp (含後綴商戶代碼)
     * @return bool
     */
    public function memberInfo(string $accountOp) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op_member_info_cache', [
            'accountOp' => $accountOp
        ]));

        return true;
    }

    /**
     * 總開關狀態
     * @param $slug "bo / api"
     * @return bool
     */
    public function platformSwitch($slug) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('platform_switch_cache', [
            'slug' => $slug
        ]));

        return true;
    }

    /**
     * 全域封鎖IP名單
     * @return bool
     */
    public function globalIPBlock() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('global_block_ip_cache', [
        ]));

        return true;
    }

    /**
     * 角色白名單
     * @return bool
     */
    public function fullAccessRoles() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('full_access_roles_cache', [
        ]));

        return true;
    }

    /**
     * 角色單一選單權限
     * @param string $role
     * @param string $menu
     * @return bool
     */
    public function roleMenuPermit(string $role, string $menu) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role_menu_permit_cache', [
            'role' => $role,
            'menu' => $menu
        ]));

        return true;
    }

    /**
     * 維護計畫
     * @param string $type
     * @return bool
     */
    public function maintainPlanning(string $type) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('maintain_planning_cache', [
            'type' => $type
        ]));

        return true;
    }

    /**
     * GF IP 白名單
     * @return bool
     */
    public function gfIP() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('gf_ip_cache', [
        ]));

        return true;
    }

    /**
     * GF幣值
     */
    public function gfCurrencyRate() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('gf_currency_rate_cache', [
        ]));
    }

    /**
     * GF幣值最小交易金額
     */
    public function gfCurrencyMinTransfer() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('gf_currency_min_transfer_cache', [
        ]));
    }
}