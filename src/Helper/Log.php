<?php


namespace GiocoPlus\PrismPlus\Helper;


use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class Log
{
    public static function get(string $name = 'system', $group = 'default') {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function error(string $message, $data = []) {
        # 將不是 array 型別的變數直接做處理
        if (!is_array($data)) {
            $value = [json_encode($data, JSON_UNESCAPED_UNICODE)];
        } else {
            $value = $data;
        }

        $log = Log::get('message', 'message');
        $log->error($message, $value);
    }

    public static function info(string $message, $data = []) {
        # 將不是 array 型別的變數直接做處理
        if (!is_array($data)) {
            $value = [json_encode($data, JSON_UNESCAPED_UNICODE)];
        } else {
            $value = $data;
        }

        $log = Log::get('message', 'message');
        $log->info($message, $value);
    }
}