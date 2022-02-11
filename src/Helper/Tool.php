<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Helper;

use Exception;

/**
 * Class Tool
 * @package App\Helper
 */
class Tool
{
    /**
     * 檢查IP是否在
     *
     * @param $src
     * @param array $list
     * @return bool
     */
    public static function IpContainChecker($src, array $list): bool {
        $src = is_array($src) ? current($src) : $src;
        $src = str_ireplace(' ', '', $src);
        $ips = explode(',', $src);
        foreach ($ips as $ip) {
            if (in_array($ip, $list)) {
                return true;
            }
            foreach($list as $slashIp) {
                if ( strpos( $slashIp, '/' ) !== false ) {
                    if (ip_in_range($ip, $slashIp)===true) {
                        return true;
                    }
                } 
            }
        }
        return false;
    }

    /**
     * 玩家代碼 裁成 player_name \ op_code
     * @param $accountOp
     * @param string $delimiter
     * @return array
     */
    public static function  MemberSplitCode($accountOp, string $delimiter = '_') {
        if (strrpos($accountOp, $delimiter) === false) {
            throw new Exception("PlayerName format not correct");
        }
        $account = substr($accountOp, 0, strrpos($accountOp, $delimiter));
        $op_code = substr($accountOp, strrpos($accountOp, $delimiter)+1, strlen($accountOp));
        return [
            'player_name' => $account,
            'op_code' => $op_code
        ];
    }

    /**
     * 清除 Mongo 的 ObjectId
     *
     * @param $src
     */
    public static function RemoveMongoObjectId($src) {
        $result = [];
        foreach ($src as $c) {
            unset($c['_id']);
            $result[] = $c;
        }
        return $result;
    }

    /**
     * 階層化
     *
     * @param array $data
     */
    public static function Tree(array $data) {
        $_tree = [];
        foreach ($data as $m) {
            unset($m['_id']);
            if (empty($m['parent_code'])) {
                $child = static::_TreeChild($data, $m['code']);
                $m['children'] = $child;
                array_push($_tree, $m);
            }
        }
        return $_tree;
    }

    /**
     * 小數點無條件捨去
     * @param $value
     * @param int $precision
     * @return float
     */
    public static function digitcut($value, $precision = 2) {
        preg_match("/^[+|-]{0,1}\d*[\.]*\d{0,$precision}/", $value, $output_array);
        $value = $output_array[0];
        return floatval($value);
    }

    /**
     * 子階層
     *
     * @param array $data
     * @param string $id
     * @return array
     */
    private static function _TreeChild(array &$data, string $id) {
        $_tree = [];
        foreach ($data as $m) {
            unset($m['_id']);
            if ($m['parent_code'] == $id) {
                $child = static::_TreeChild($data, $m['code']);
                $m['children'] = $child;
                array_push($_tree, $m);
            }
        }
        return $_tree;
    }


}
