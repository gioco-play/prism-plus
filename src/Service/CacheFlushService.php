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
     * 清除 營運商
     * @param $code
     * @return bool
     */
    public function operator($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 營運商幣值表
     * @param $code
     * @return bool
     */
    public function operatorCurrencyRate($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op-currency-rate', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 公司
     * @param $code
     * @return bool
     */
    public function company($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 遊戲商
     * @param $code
     * @return bool
     */
    public function vendor($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 遊戲清單
     * @param $vendorCode
     * @return bool
     */
    public function games($vendorCode) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor-game-update', [ 'vendor_code' => $vendorCode]));

        return true;
    }

    /**
     * 清除 營運商 - 公司
     * @param $code
     * @return bool
     */
    public function companyOpCodes($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp-opcodes-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 角色選單
     * @param $role
     * @return bool
     */
    public function roleMenu($role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role-menu-update', [ 'role' => $role]));

        return true;
    }

    /**
     * 清除 角色選單權限
     * @param $role
     * @return bool
     */
    public function roleMenuPermit($role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role-menu-permit-update', [ 'role' => $role]));

        return true;
    }

    /**
     * 清除 玩家
     * @param $accountOp
     * @return bool
     */
    public function memberInfo($accountOp) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op-member-info-update', [ 'accountOp' => $accountOp]));

        return true;
    }

    /**
     * 平台開關狀態
     * @param $slug
     * @return bool
     */
    public function platformSwitch($slug) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('platform-switch-update', [ 'slug' => $slug]));
        return true;
    }

    /**
     * 全域封鎖IP名單
     * @return bool
     */
    public function globalBlockIp() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('global-block-ip', []));
        return true;
    }

    /**
     * 角色白名單
     */
    public function fullAccessRoles() {
        $this->dispatcher->dispatch(new DeleteListenerEvent('full-access-roles', []));
        return true;
    }
}