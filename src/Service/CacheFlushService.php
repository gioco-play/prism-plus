<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\RedisFactory;
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
     * @Inject()
     * @var RedisFactory
     */
    protected $redisFactory;

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
        $this->dispatcher->dispatch(new DeleteListenerEvent('admin_user_cache', [
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
     * 總開關狀態
     * @param $slug "bo / api"
     * @return bool
     */
    public function platformSwitch($slug) {
        $key = 'platform_switch_' . strtolower($slug);
        $v3Del = true;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $v3Del = $this->redisFactory->get('v3')->del($key);
        }
        $v2Del = $this->redisFactory->get('default')->del($key);

        return $v3Del && $v2Del;
    }

    /**
     * 全域封鎖IP名單
     * @return bool
     */
    public function globalIPBlock() {
        $key = 'global_ip_block';

        $v3Del = true;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $v3Del = $this->redisFactory->get('v3')->del($key);
        }
        $v2Del = $this->redisFactory->get('default')->del($key);

        return $v3Del && $v2Del;
    }
    
    /**
     * 全域 IP 白名單
     * @return bool
     */
    public function globalIPWhite() {
        $key = 'global_white_ip';

        $v3Del = true;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $v3Del = $this->redisFactory->get('v3')->del($key);
        }
        $v2Del = $this->redisFactory->get('default')->del($key);

        return $v3Del && $v2Del;
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
        $key = 'gf_currency_rate';

        $v3Del = true;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $v3Del = $this->redisFactory->get('v3')->del($key);
        }
        $v2Del = $this->redisFactory->get('default')->del($key);

        return $v3Del && $v2Del;
    }

    /**
     * GF幣值最小交易金額
     */
    public function gfCurrencyMinTransfer() {
        $key = 'gf_currency_min_transfer';

        $v3Del = true;
        if (!empty(env("REDIS_SENTINEL_NODE_V3"))) {
            $v3Del = $this->redisFactory->get('v3')->del($key);
        }
        $v2Del = $this->redisFactory->get('default')->del($key);

        return $v3Del && $v2Del;
    }
}
