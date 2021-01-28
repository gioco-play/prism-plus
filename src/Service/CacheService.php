<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Service;

use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
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
     * 管理者基本資料
     * @param string $account
     * @Cacheable(prefix="admin_user_info", ttl=600, value="_#{account}", listener="admin_user_info_cache")
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
     * @Cacheable(prefix="admin_user", ttl=600, value="_#{uid}", listener="admin_user_cache")
     */
    public function adminUser(string $uid) {
        $this->dbDefaultPool();
        return current($this->mongodb->fetchAll('admin_users', ['_id' => $uid]));
    }

    /**
     * 營運商
     * @deprecated
     * @param string $code
     * @Cacheable(prefix="op", ttl=600, value="_#{code}", listener="op_cache")
     */
    public function operator(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商
     * @deprecated
     * @param string $operator_token
     * @Cacheable(prefix="op_token", ttl=600, value="_#{operator_token}", listener="op_token_cache")
     */
    public function operatorByToken(string $operator_token) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('operators', [
            'operator_token' => $operator_token
        ]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商幣值表
     * @deprecated
     * @param string $code
     * @Cacheable(prefix="op_currency_rate", ttl=600, value="_#{code}", listener="op_currency_rate_cache")
     */
    public function operatorCurrencyRate(string $code) {
        $this->dbDefaultPool();
        $operator = $this->operator($code);

        if ($operator) {
            return collect($operator['currency_rate'])->pluck('rate', 'vendor')->toArray();
        }

        return [];
    }

    /**
     * 運營商 封鎖遊戲
     * @deprecated
     * @param string $code
     * @param string $vendorCode
     * @return array
     * @Cacheable(prefix="op_block_game", ttl=600, value="_#{code}_#{vendorCode}", listener="op_block_game_cache")
     */
    public function operatorBlockGames(string $code, string $vendorCode) {
        $this->dbDefaultPool();
        $operator = current($this->operator($code));
        $blacklist = $operator["game_blacklist"]??[];
        return $blacklist[$vendorCode] ?? [];
    }

    /**
     * 公司
     * @param string $code
     * @Cacheable(prefix="company", ttl=600, value="_#{code}", listener="company_cache")
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
     * 遊戲商
     * @deprecated
     * @param string $code
     * @Cacheable(prefix="vendor", ttl=600, value="_#{code}", listener="vendor_cache")
     */
    public function vendor(string $code) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('vendors', ['code' => $code]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲清單
     * @deprecated
     * @param string $vendorCode
     * @Cacheable(prefix="vendor_games", ttl=600, value="_#{vendorCode}", listener="vendor_games_cache")
     */
    public function games(string $vendorCode) {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('games', ['vendor_code' => $vendorCode]);

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 遊戲
     * @deprecated
     * @param string $gameCode
     * @Cacheable(prefix="vendor_game", ttl=600, value="_#{gameCode}", listener="vendor_game_cache")
     */
    public function game(string $gameCode) {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('games', ['game_code' => $gameCode]));

        if ($data) {
            return $data;
        }

        return null;
    }

    /**
     * 營運商 - 公司
     * @param string $code
     * @return array
     * @Cacheable(prefix="comp_opcodes", ttl=600, value="_#{code}", listener="comp_opcodes_cache")
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
     * @Cacheable(prefix="comp_op_currency", ttl=600, value="_#{code}_#{currency}", listener="comp_op_currency_cache")
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
                if (strtolower($c['currency']) === strtolower($currency)) {
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
     * @Cacheable(prefix="role_menu", ttl=600, value="_#{role}", listener="role_menu_cache")
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
     * @Cacheable(prefix="role_menu_permits", ttl=600, value="_#{role}", listener="role_menu_permits_cache")
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
     * @Cacheable(prefix="op_member_info", ttl=600, value="_#{accountOp}", listener="op_member_info_cache")
     */
    public function memberInfo(string $accountOp, string $delimiter = '_') {
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
                return [
                    'operator' => $this->opCache->basic(strtoupper($op)),
                    'player' => current($result)
                ];
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
     * @Cacheable(prefix="platform_switch", ttl=600, value="_#{slug}", listener="platform_switch_cache")
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
     * @Cacheable(prefix="global_block_ip", ttl=600, listener="global_block_ip_cache")
     */
    public function globalIPBlock() {
        $this->dbDefaultPool();
        $data = current($this->mongodb->fetchAll('platform', ['slug' => 'block_ip']));
        if ($data) {
            return $data;
        }
        return [];
    }

    /**
     * 角色白名單
     * @Cacheable(prefix="full_access_roles", ttl=600, listener="full_access_roles_cache")
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
     * @Cacheable(prefix="role_menu_permit", ttl=600, value="_#{role}_#{menu}", listener="role_menu_permit_cache")
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
     * @Cacheable(prefix="maintain_planning", ttl=600, value="_#{type}", listener="maintain_planning_cache")
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
     * @Cacheable(prefix="gf_ip", ttl=600, listener="gf_ip_cache")
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
     * 錢包代碼
     * @deprecated
     * @Cacheable(prefix="wallet_code", ttl=600, listener="wallet_code_cache")
     */
    public function walletCodes() {
        $this->dbDefaultPool();
        $data = $this->mongodb->fetchAll('vendors');
        $walletCodes = collect($data)->pluck('wallet_code')->toArray();
        if ($walletCodes) {
            return $walletCodes;
        }
        return [];
    }
}