<?php
declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace GiocoPlus\PrismPlus\Helper;

/**
 *
 * API 返回格式
 *
 * Class ApiResponse
 * @package App\Helper
 */
class ApiResponse {

    const SUCCESS  = [
        'code' => 1,
        'msg' => '成功'
    ];

    const FIELD_MISSING = [
        'code' => 2,
        'msg' => '資料欄位有誤'
    ];

    const DATA_EXIST = [
        'code' => 3,
        'msg' => '資料已存在'
    ];

    const DATA_NOT_EXIST = [
        'code' => 4,
        'msg' => '資料不存在'
    ];

    const UPDATE_FAIL = [
        'code' => 5,
        'msg' => '更新失敗, 請檢查資料（若資料無異動視為更新失敗）'
    ];

    const PRODUCT_NEED_ACK = [
        'code' => 7100,
        'msg' => '產品尚未配置'
    ];

    const PRODUCT_GAME_MAINTAIN = [
        'code' => 7101,
        'msg' => '遊戲維護'
    ];

    const PRODUCT_GAME_DECOMMISSION = [
        'code' => 7102,
        'msg' => '遊戲停用'
    ];

    const VENDOR_REQUEST_FAIL = [
        'code' => 7200,
        'msg' => '產品商來源請求異常'
    ];

    const TRANS_CURRENCY_RATE_EMPTY = [
        'code' => 8101,
        'msg' => '交易幣值轉換不存在'
    ];

    const TRANS_BALANCE_SHORT = [
        'code' => 8102,
        'msg' => '錢包餘額不足'
    ];

    const TRANS_WALLET_EMPTY = [
        'code' => 8103,
        'msg' => '錢包初始化失敗'
    ];

    const TRANS_AMOUNT_ERROR = [
        'code' => 8104,
        'msg' => '交易金額有誤'
    ];

    const TRANS_SEAMLESS_ERROR = [
        'code' => 8105,
        'msg' => '請檢查類單一配置'
    ];

    const TRANS_BALANCE_FAIL = [
        'code' => 8106,
        'msg' => '錢包交易失敗'
    ];

    const TRANS_WALLET_INIT_FAIL = [
        'code' => 8107,
        'msg' => '錢包初始化失敗'
    ];

    const ROUTER_NOT_FOUND = [
        'code' => 9100,
        'msg' => '找不到路由'
    ];

    const HTTP_METHOD_NOT_ALLOWED = [
        'code' => 9101,
        'msg' => '請求方法不允許'
    ];

    const IP_NOT_ALLOWED = [
        'code' => 9201,
        'msg' => 'IP存取限制'
    ];

    const IP_BLOCKED = [
        'code' => 9202,
        'msg' => '黑名單IP存取限制'
    ];

    const JWT_AUTH_FAIL = [
        'code' => 9300,
        'msg' => 'JWT認證失敗'
    ];

    const ADMIN_AUTH_FAIL = [
        'code' => 9400,
        'msg' => '帳號或密碼錯誤'
    ];

    const ADMIN_OLD_PASS_FAIL = [
        'code' => 9401,
        'msg' => '舊密碼錯誤'
    ];

    const ADMIN_FORBIDDEN = [
        'code' => 9402,
        'msg' => '帳號停權'
    ];

    const MAINTAIN = [
        'code' => 9500,
        'msg' => '維護中'
    ];

    const DECOMMISSION = [
        'code' => 9500,
        'msg' => '已停用'
    ];

    /**
     * 結果
     *
     * @param $data
     * @param array $status
     * @return array
     */
    public static function result($data = [], array $status = self::SUCCESS) {
        $result = [
            'status' => $status['code'],
            'message' => $status['msg']
        ];

        $result['data'] = $data;

        return $result;
    }
}