<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;

/**
 * 快取
 * Class CacheService
 * @package GiocoPlus\PrismPlus\Service
 */
class CacheService
{

    /**
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * MongoDb 連結池
     * @var string
     */
    protected $poolName = "default";

    public function __construct(ContainerInterface $container) {
        $this->mongodb = $container->get(MongoDb::class);
        $this->mongodb = $this->mongodb->setPool($this->poolName);
    }

    /**
     * 管理者基本資料
     * @param string $account
     * @Cacheable(prefix="admin_user_info", ttl=60, value="_#{account}", listener="admin-user-update")
     */
    public function adminUserInfo(string $account) {
        $role = current($this->mongodb->fetchAll('admin_user_roles', ['account' => $account]));
        $_company = current($this->mongodb->fetchAll('admin_user_company', ['account' => $account]));
        $company = $this->company($_company['company_code']??"unknown");
        return [
            'role' => $role['role']??"unknown-role",
            'company_code' => $company['code']??"unknown-code",
            'company_name' => $company['name']??"unknown-name"
        ];
    }

    /**
     * 管理者帳號
     * @param string $uid
     * @Cacheable(prefix="admin_user", ttl=120, value="_#{uid}", listener="admin-user-update")
     */
    public function adminUser(string $uid) {
        return current($this->mongodb->fetchAll('admin_users', ['_id' => $uid]));
    }

    /**
     * 營運商
     * @param string $code
     * @Cacheable(prefix="op", ttl=300, value="_#{code}", listener="op-update")
     */
    public function operator(string $code) {

        $data = current($this->mongodb->fetchAll('operators', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商幣值表
     * @param string $code
     * @Cacheable(prefix="op_currency_rate", ttl=300, value="_#{code}", listener="op-currency-rate")
     */
    public function operatorCurrencyRate(string $code) {
        $operator = current($this->operator($code));

        if ($operator) {
            return collect($operator['currency_rate'])->pluck('rate', 'vendor');
        }

        return null;
    }

    /**
     * 公司
     * @param string $code
     * @Cacheable(prefix="comp", ttl=60, value="_#{code}", listener="comp-update")
     */
    public function company(string $code) {

        $data = current($this->mongodb->fetchAll('companies', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲商
     * @param string $code
     * @Cacheable(prefix="vendor", ttl=300, value="_#{code}", listener="vendor-update")
     */
    public function vendor(string $code) {

        $data = current($this->mongodb->fetchAll('vendors', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲清單
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_game", ttl=300, value="_#{vendorCode}", listener="vendor-game-update")
     */
    public function games(string $vendorCode) {
        $data = $this->mongodb->fetchAll('games', ['vendor_code' => $vendorCode]);

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲商 請求參數列表
     * @Cacheable(prefix="list_request_params", ttl=60, listener="vendor-request-param-list")
     */
    public function listRequestParams() {

        $data = $this->mongodb->fetchAll('vendors');

        if ($data) {
            return $data;
        }

        return [];
    }

    /**
     * 營運商 - 公司
     * @param string $code
     * @return array
     * @Cacheable(prefix="comp_opcodes", ttl=60, value="_#{code}", listener="comp-opcodes-update")
     */
    public function companyOpCodes(string $code) : array {

        $comp = $this->mongodb->fetchAll('companies', ['type' => 'comp' ,'code' => $code]);

        if ($comp) {
            return $this->_subCompanyOpcodes($comp);
        }

        return [];
    }

    /**
     * 取得公司別的商戶代碼
     * @param $comp
     */
    private function _subCompanyOpcodes($comp) {
        $_code = [];
        foreach ($comp as $c) {
            if ($c['type'] === 'comp') {
                $_comp = $this->mongodb->fetchAll('companies', ['parent_code' => $c['code'], 'status' => 'online'], [
                    'sort' => ['sort'=>1]
                ]);
                $__codes = $this->_subCompanyOpcodes($_comp);
                $p_comp = [
                    'code' => $c['code'],
                    'name' => $c['name'],
                    'children' => $__codes
                ];
                $_code[] = $p_comp;
//                $_code = array_merge($_code, $__codes);
            } else {
                $_code[] = [
                    'parent_code' => $c['parent_code'],
                    'code' => $c['code'],
                    'name' => $c['name']
                ];
            }
        }
        return $_code;
    }


    /**
     * 角色選單
     * @param string $role
     * @Cacheable(prefix="role_menu", ttl=60, value="_#{role}", listener="role-menu-update")
     */
    public function roleMenu(string $role) {

        $filter = ['role' => $role];
        $filter_menu = [];
        if ($role !== 'supervisor') {
            $roles = $this->mongodb->fetchAll('admin_role_menus', $filter);
            $menu_codes = collect($roles)->pluck('menu_code')->toArray();

            $filter_menu = ['code' => ['$in' => $menu_codes]];
        }

        $data = $this->mongodb->fetchAll('menus',
            $filter_menu, [
                'sort' => ['sort'=>1]
            ]
        );

        $roleMenuPermit = $this->roleMenuPermit($role);
        $menus = [];
        if ($data) {
            foreach ($data as $menu) {
                $permits = collect($roleMenuPermit)->where('menu', $menu['code'])->first();
                $menu['permit'] = $permits['permit'] ?? [];
                $menu['hidden_fields'] = $permits['hidden_fields'] ?? [];
                $menus[] = $menu;
            }
            return $menus;
        }

        return [];
    }

    /**
     * 角色選單權限
     * @param string $role
     * @Cacheable(prefix="role_menu_permit", ttl=60, value="_#{role}", listener="role-menu-permit-update")
     */
    public function roleMenuPermit(string $role) {
        $filter =  ['role' => $role];
        if ($role === 'supervisor') {
            return [];
        }

        return $this->mongodb->fetchAll('admin_role_menu_permissions', $filter);
    }

    /**
     * 查詢會員資料
     * @param string $accountOp (含後綴商戶代碼)
     * @param string $delimiter (目前遇到的有 "_"（預設） \ "0" \ "@")
     * @return mixed
     * @Cacheable(prefix="op_member_info", ttl=60, value="_#{accountOp}", listener="op-member-info-update")
     */
    public function memberInfo(string $accountOp, string $delimiter = '_') {
        list($account, $op) = array_values(Tool::MemberSplitCode($accountOp, $delimiter));
        $dbManager = new DbManager();
        $pg = $dbManager->opPostgreDb(strtolower($op));
        $result = $pg->query("SELECT * FROM members WHERE player_name='{$account}' OR member_code='{$account}'");
        if ($result) {
            return current($pg->fetchAll($result));
        }
        return $result;
    }

    /**
     * 總開關狀態
     * @param $slug "bo / api"
     * @return false|mixed
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="platform_switch", ttl=300, value="_#{$slug}", listener="platform-switch-update")
     */
    public function platformSwitch($slug) {
        $filter =  ['slug' => $slug];
        $data = current($this->mongodb->fetchAll('platform', $filter));
        if ($data) {
            return $data['status'];
        }
        return false;
    }

    /**
     * 全域封鎖IP名單
     * @Cacheable(prefix="global_block_ip", ttl=300, listener="global-block-ip")
     */
    public function globalBlockIp() {
        $data = current($this->mongodb->fetchAll('platform', ['slug' => 'block_ip']));
        if ($data) {
            return $data;
        }
        return [];
    }

}