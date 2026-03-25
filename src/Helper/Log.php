<?php

namespace GiocoPlus\PrismPlus\Helper;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class Log
{
    /**
     * 防禦性標記，防止 Logger 觸發 Exception 後引發無限死循環
     */
    private static $isLogging = false;
    private const COROUTINE_GUARD_KEY = '__prism_log_is_logging';

    public static function get(string $name = 'system', string $group = 'default')
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function error(string $message, $data = [])
    {
        self::writeLog('error', $message, $data);
    }

    public static function info(string $message, $data = [])
    {
        self::writeLog('info', $message, $data);
    }

    /**
     * 共用的日誌寫入邏輯
     */
    private static function writeLog(string $level, string $message, $data = [])
    {
        // 如果正在寫入日誌時又被觸發，直接中斷以打破循環
        if (self::isLoggingInProgress()) {
            // 將被吞掉的深層錯誤寫入 PHP/Swoole 底層日誌，方便追蹤
            error_log("Log Loop Detected! Level: {$level}, Message: {$message}");
            return;
        }

        self::setLoggingGuard(true);

        try {
            // 避免手動 json_encode 導致隱藏錯誤，統一轉為陣列交給 Monolog 處理
            $value = is_array($data) ? $data : ['data' => $data];

            // 獲取設定檔中 'message' 區塊的 Logger (寫入 service.log)
            $log = self::get('message', 'message');
            $log->{$level}($message, $value);
        } catch (\Throwable $e) {
            // 捕捉寫入日誌時發生的任何錯誤 (例如權限不足、資料夾不存在)
            error_log("Logging failed in Log Helper: " . $e->getMessage());
        } finally {
            // 確保無論成功或失敗，都會釋放標記
            self::setLoggingGuard(false);
        }
    }

    private static function isLoggingInProgress(): bool
    {
        $context = self::getCoroutineContext();
        if ($context !== null) {
            return !empty($context[self::COROUTINE_GUARD_KEY]);
        }
        return self::$isLogging;
    }

    private static function setLoggingGuard(bool $isLogging): void
    {
        $context = self::getCoroutineContext();
        if ($context !== null) {
            if ($isLogging) {
                $context[self::COROUTINE_GUARD_KEY] = true;
            } else {
                unset($context[self::COROUTINE_GUARD_KEY]);
            }
            return;
        }
        self::$isLogging = $isLogging;
    }

    private static function getCoroutineContext()
    {
        if (!class_exists(\Swoole\Coroutine::class)) {
            return null;
        }

        if (!method_exists(\Swoole\Coroutine::class, 'getCid') || !method_exists(\Swoole\Coroutine::class, 'getContext')) {
            return null;
        }

        if (\Swoole\Coroutine::getCid() <= 0) {
            return null;
        }

        return \Swoole\Coroutine::getContext();
    }
}
