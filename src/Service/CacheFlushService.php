<?php

declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Service;


use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
/**
 * 清除快取
 * Class CacheFlushService
 * @package GiocoPlus\EZAdmin\Service
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
     *
     * @param $code
     * @return bool
     */
    public function operator($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('op-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 公司
     *
     * @param $code
     * @return bool
     */
    public function company($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 遊戲商
     *
     * @param $code
     * @return bool
     */
    public function vendor($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('vendor-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 營運商 - 公司
     *
     * @param $code
     * @return bool
     */
    public function companyOpCodes($code) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('comp-opcodes-update', [ 'code' => $code]));

        return true;
    }

    /**
     * 清除 角色選單
     *
     * @param $role
     * @return bool
     */
    public function roleMenu($role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role-menu-update', [ 'role' => $role]));

        return true;
    }

    /**
     * 清除 角色選單權限
     *
     * @param $role
     * @return bool
     */
    public function roleMenuPermit($role) {
        $this->dispatcher->dispatch(new DeleteListenerEvent('role-menu-permit-update', [ 'role' => $role]));

        return true;
    }
}