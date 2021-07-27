<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
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
     * @Inject(lazy=true)
     * @var OperatorCacheService
     */
    protected $opCache;

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
     * 角色清單
     * @return array
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="admin_user_roles", value="_#{account}", listener="admin_user_roles_cache")
     */
    public function adminRoles(){
        $this->dbDefaultPool();
        $roles = $this->mongodb->fetchAll('admin_roles', []);
        if ($roles) {
            return $roles;
        }
        return [];
    }

    /**
     * 管理者基本資料
     * @param string $account
     * @Cacheable(prefix="admin_user_info", value="_#{account}", listener="admin_user_info_cache")
     */
    public function adminUserInfo(string $account) {
        $this->dbDefaultPool();
        $role = current($this->mongodb->fetchAll('admin_user_roles', ['account' => $account]));
        $_company = current($this->mongodb->fetchAll('admin_user_company', ['account' => $account]));
        $company = $this->company($_company['company']??"unknown");
        return [
            'role' => $role['role']??"unknown-role",
            'company' => $company['code']??"unknown-code",
            'company_name' => $company['name']??"unknown-name"
        ];
    }

    /**
     * 管理者帳號
     * @param string $uid
     * @Cacheable(prefix="admin_user", value="_#{uid}", listener="admin_user_cache")
     */
    public function adminUser(string $uid) {
        $this->dbDefaultPool();
        return current($this->mongodb->fetchAll('admin_users', ['_id' => $uid]));
    }

    /**
     * 公司
     * @param string $code
     * @Cacheable(prefix="company", value="_#{code}", listener="company_cache")
     */
    public function company(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('companies', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商 - 公司
     * @param string $code
     * @return array
     * @Cacheable(prefix="comp_opcodes", value="_#{code}", listener="comp_opcodes_cache")
     */
    public function companyOpCodes(string $code) : array {
        $this->dbDefaultPool();
        $comp = $this->mongodb->fetchAll('companies', ['type' => 'company' ,'code' => $code]);

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
            if ($c['type'] === 'company') {
                $this->dbDefaultPool();
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
                    'name' => $c['name'],
                    'currency' => $c['currency']
                ];
            }
        }
        return $_code;
    }

    /**
     * 營運商 - 幣別
     * @param string $code
     * @param string $currency
     * @return array
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="comp_op_currency", value="_#{code}_#{currency}", listener="comp_op_currency_cache")
     */
    public function companyOpCurrency(string $code, string $currency) : array {
        $this->dbDefaultPool();
        $comp = $this->mongodb->fetchAll('companies', ['type' => 'company' ,'code' => $code]);

        if ($comp) {
            return $this->_subCompanyOpCurrency($comp, $currency);
        }

        return [];
    }

    /**
     * 篩選公司別的幣別
     * @param $comp
     * @param string $currency
     * @return array
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     */
    private function _subCompanyOpCurrency($comp, string $currency) {
        $_code = [];
        foreach ($comp as $c) {
            if ($c['type'] === 'company') {
                $this->dbDefaultPool();
                $_comp = $this->mongodb->fetchAll('companies', ['parent_code' => $c['code'], 'status' => 'online'], [
                    'sort' => ['sort'=>1]
                ]);
                $__codes = $this->_subCompanyOpCurrency($_comp, $currency);
                $_code = array_merge($_code, $__codes);
            } else {
                if (strtoupper($c['currency']) === strtoupper($currency)) {
                    $_code[] = [
                        'parent_code' => $c['parent_code'],
                        'code' => $c['code'],
                        'name' => $c['name'],
                        'currency' => $c['currency'],
                    ];
                }
            }
        }
        return $_code;
    }


    /**
     * 角色選單
     * @param string $role
     * @Cacheable(prefix="role_menu", value="_#{role}", listener="role_menu_cache")
     */
    public function roleMenu(string $role) {

        $filter = ['role' => $role];
        $filter_menu = [];
        if ($role !== 'supervisor') {
            $this->dbDefaultPool();
            $roles = $this->mongodb->fetchAll('admin_role_menus', $filter);
            $menus = collect($roles)->pluck('menu')->toArray();
            $filter_menu = ['code' => ['$in' => $menus]];
        }

        $data = $this->mongodb->fetchAll('menus',
            $filter_menu, [
                'sort' => ['sort'=>1]
            ]
        );

        $roleMenuPermits = $this->roleMenuPermits($role);
        $menus = [];
        if ($data) {
            foreach ($data as $menu) {
                $permits = collect($roleMenuPermits)->where('menu', $menu['code'])->first();
                $menu['permits'] = $permits['permits'] ?? [];
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
     * @Cacheable(prefix="role_menu_permits", value="_#{role}", listener="role_menu_permits_cache")
     */
    public function roleMenuPermits(string $role) {
        $this->dbDefaultPool();
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
     */
    public function memberInfo(string $accountOp, string $delimiter = '_') {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        if ($data = $redis->get($accountOp)) {
            return json_decode($data, true);
        }
        $this->dbDefaultPool();
        list($account, $op) = array_values(Tool::MemberSplitCode($accountOp, $delimiter));
        $dbManager = new DbManager();
        $pg = $dbManager->opPostgreDb(strtolower($op));
        $result = $pg->query("SELECT player_name, member_code, currency, status 
                                FROM members 
                            WHERE player_name='{$account}' OR member_code='{$account}'");
        try {
            $result = $pg->fetchAll($result);
            if ($result) {
                $data = [
                    'operator' => $this->opCache->basic(strtoupper($op)),
                    'player' => current($result)
                ];
                $bool = $redis->setex($accountOp, 30, json_encode($data));
                if ($bool) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            return [
                'operator' => false,
                'player' => false
            ];
        }
        return [
            'operator' => false,
            'player' => false
        ];
    }

    /**
     * 總開關狀態
     * @param $slug "bo / api"
     * @return false|mixed
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="platform_switch", value="_#{slug}", listener="platform_switch_cache")
     */
    public function platformSwitch($slug) {
        $this->dbDefaultPool();
        $filter =  ['slug' => $slug];
        $data = current($this->mongodb->fetchAll('platform', $filter));
        if ($data) {
            return $data['status'];
        }
        return false;
    }

    /**
     * 全域封鎖IP名單
     * @Cacheable(prefix="global_block_ip", listener="global_block_ip_cache")
     */
    public function globalIPBlock() {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('platform', ['slug' => 'block_ip']));
        if ($data) {
            return $data['ip'];
        }
        return [];
    }
    
    /**
     * 全域IP白名單
     * @Cacheable(prefix="global_white_ip", listener="global_white_ip_cache")
     */
    public function globalIPWhite() {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('platform', ['slug' => 'white_ip']));
        if ($data) {
            return $data['ip'];
        }
        return [];
    }

    /**
     * 角色白名單
     * @Cacheable(prefix="full_access_roles", listener="full_access_roles_cache")
     */
    public function fullAccessRoles() {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('platform', ['slug' => 'full_access_role']));
        if ($data) {
            return $data['roles'];
        }
        return [];
    }

    /**
     * 角色單一選單權限
     * @param string $role
     * @param string $menu
     * @return array|mixed
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     * @Cacheable(prefix="role_menu_permit", value="_#{role}_#{menu}", listener="role_menu_permit_cache")
     */
    public function roleMenuPermit(string $role, string $menu) {
        $this->dbDefaultPool();
        if ($role === 'supervisor') {
            return true;
        }

        $data = current($this->mongodb->fetchAll('admin_role_menu_permissions',
            [
                'role' => $role,
                'menu' => $menu
            ]
        ));

        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * 維護計畫
     * @param string $type
     * @Cacheable(prefix="maintain_planning", value="_#{type}", listener="maintain_planning_cache")
     */
    public function maintainPlanning(string $type) {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('maintain_planning',
            [
                'type' => $type,
                'valid' => true
            ]
        );
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * GF IP 白名單
     * @Cacheable(prefix="gf_ip", listener="gf_ip_cache")
     */
    public function gfIP() {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('global_params', ['code' => 'gf_ip']));
        if ($data) {
            return $data['params'];
        }
        return [];
    }

    /**
     * GF幣值
     * @Cacheable(prefix="gf_currency_rate", listener="gf_currency_rate_cache")
     */
    public function gfCurrencyRate() {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('gf_exchange_rate');
        if ($data) {
            return collect($data)->pluck('rate', 'code')->toArray();
        }
        return [];
    }

    /**
     * GF幣值最小交易金額
     * @Cacheable(prefix="gf_currency_min_transfer", listener="gf_currency_min_transfer_cache")
     */
    public function gfCurrencyMinTransfer() {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('gf_exchange_rate');
        if ($data) {
            return collect($data)->pluck('min_transfer', 'code')->toArray();
        }
        return [];
    }

}
