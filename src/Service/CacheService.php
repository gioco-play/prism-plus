<?php

declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Service;

use GiocoPlus\Mongodb\MongoDb;
use GiocoPlus\Mongodb\Pool\PoolFactory;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;

/**
 * 快取
 * Class CacheService
 * @package GiocoPlus\EZAdmin\Service
 */
class CacheService
{

    /**
     * @var MongoDb
     */
    protected $mongodb;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ContainerInterface $container) {
        $this->mongodb = $container->get(MongoDb::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    /**
     * 管理者基本資料
     *
     * @param string $account
     *
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
     *
     * @param string $uid
     * @Cacheable(prefix="admin_user", ttl=60, value="_#{uid}", listener="admin-user-update")
     */
    public function adminUser(string $uid) {
        $user = current($this->mongodb->fetchAll('admin_users', ['_id' => $uid]));
        return $user;
    }

    /**
     * 營運商
     *
     * @param string $code
     *
     * @Cacheable(prefix="op", ttl=60, value="_#{code}", listener="op-update")
     */
    public function operator(string $code) {

        $data = current($this->mongodb->fetchAll('operators', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商 MongoDb 配置参数
     *
     * @param string $code
     * @return array
     * @Cacheable(prefix="op_mongodb_cfg", ttl=60, value="_#{code}", listener="op-mongodb-cfg")
     */
    public function opMongoDbConfig(string $code) {
        $op = $this->operator($code);
        $dbConn = $op['db']->mongodb;
        return mongodb_pool_config($dbConn->host, $dbConn->db_name, intval($dbConn->port), $dbConn->replica, $dbConn->read_preference??"primary");
    }

    /**
     * 公司
     *
     * @param string $code
     *
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
     *
     * @param string $code
     *
     * @Cacheable(prefix="vendor", ttl=60, value="_#{code}", listener="vendor-update")
     */
    public function vendor(string $code) {

        $data = current($this->mongodb->fetchAll('vendors', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲商 請求參數列表
     *
     *
     * @Cacheable(prefix="list_request_params", ttl=60, listener="vendor-request-param-list")
     */
    public function listRequestParams() {

        $data = $this->mongodb->fetchAll('vendors');

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商 - 公司
     *
     * @param string $code
     * @param bool $full
     * @return array
     *
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
     *
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
     *
     * @param string $role
     *
     * @Cacheable(prefix="role_menu", ttl=60, value="_#{role}", listener="role-menu-update")
     */
    public function roleMenu(string $role) {

        $filter =  ['role' => $role];
        if ($role === 'supervisor') {
            $filter = [];
        }

        $roles = $this->mongodb->fetchAll('admin_role_menus', $filter);
        $menu_codes = collect($roles)->pluck('menu_code')->toArray();

        $data = $this->mongodb->fetchAll('menus',
            [
                'code' => [
                    '$in' => $menu_codes
                ]
            ], [
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

        return null;
    }

    /**
     * 角色選單權限
     *
     * @param string $role
     *
     * @Cacheable(prefix="role_menu_permit", ttl=60, value="_#{role}", listener="role-menu-permit-update")
     */
    public function roleMenuPermit(string $role) {
        $filter =  ['role' => $role];
        if ($role === 'supervisor') {
            return [];
        }

        return $this->mongodb->fetchAll('admin_role_menu_permissions', $filter);
    }
}