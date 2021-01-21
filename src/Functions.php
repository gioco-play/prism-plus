<?php

declare(strict_types=1);


use MongoDB\BSON\UTCDateTime;

if (!function_exists('micro_timestamp')) {
    /**
     * 毫秒時間戳
     *
     * @return int
     */
    function micro_timestamp(): int {
        return intval(round(microtime(true) * 1000));
    }
}


if (!function_exists('gen_trace_id')) {

    /**
     * 注單流水號
     * @param string $player_name
     * @param string $vendor_code
     * @param string $action
     * @param string $vendor_unique_id
     * @param bool $ts
     * @return string
     */
    function gen_trace_id(string $player_name, string $vendor_code, string $action, string $vendor_unique_id, bool $ts = false): string {
        $verdorCode = strtoupper($vendor_code);
        $action = strtoupper($action);

        if ($ts === false) {
            return "{$player_name}::{$verdorCode}::{$action}::{$vendor_unique_id}";
        } else {
            return "{$player_name}::{$verdorCode}::{$action}::{$vendor_unique_id}-" . (int) round(microtime(true) * 1000);
        }
    }

}

if (!function_exists('gen_rand_string')) {

    /**
     * 隨機字串
     *
     * @param integer $length
     * @return string
     */
    function gen_rand_string(int $length = 10): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

if (!function_exists('gen_rand_int')) {

    /**
     * 隨機數字
     *
     * @param integer $length
     * @return string
     */
    function gen_rand_int(int $length = 5): string {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

if (!function_exists('gen_timeout_order_log')) {

    /**
     * 上 / 下分失敗訂單格式
     * @param string $action
     * @param string $operator_code
     * @param string $vendor_code
     * @param string $player_name
     * @param string $order_no
     * @param float $amount
     * @return array
     */
    function gen_timeout_order_log(string $action, string $operator_code, string $vendor_code, string $player_name, string $order_no, float $amount): array {
        return [
            "action" => $action,
            "operator_code" => $operator_code,
            "vendor_code" => $vendor_code,
            "player_name" => $player_name,
            "order_no" => $order_no,
            "amount" => $amount,
            "status" => "fail",
            "created_at" => new UTCDateTime,
            "created_date" => date('Y-m-d')
        ];
    }

}

if (!function_exists('gen_order_no')) {

    /**
     * 訂單號
     * @param string $operator_code
     * @param int $suffix_length
     * @return string
     */
    function gen_order_no(string $operator_code, int $suffix_length = 5): string {
        $prefix = strtoupper($operator_code);
        $time  = intval(microtime(true)*1000);
        $suffix = gen_rand_string($suffix_length);
        return "$prefix{$time}$suffix";
    }

}

if (!function_exists('base64url_encode')) {

    /**
     * URL base64 encode
     * @param string $data
     * @return string
     */
    function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

}

if (!function_exists('base64url_decode')) {

    /**
     * URL base64 encode
     * @param $data
     * @return false|string
     */
    function base64url_decode($data) {
        if (empty($data)) return false;
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

}