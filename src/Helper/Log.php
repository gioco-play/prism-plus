<?php


namespace GiocoPlus\PrismPlus\Helper;


use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class Log
{
    public static function get(string $name = 'system', $group = 'default') {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function error(string $message, array $data = []) {
        $log = Log::get('message', 'message');
        $log->error($message, $data);
    }

    public static function info(string $message, array $data = []) {
        $log = Log::get('message', 'message');
        $log->info($message, $data);
    }
}