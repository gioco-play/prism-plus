<?php

namespace GiocoPlus\PrismPlus\Helper;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class Log
{
    private static $isLogging = false;
    private const COROUTINE_GUARD_KEY = '__prism_log_is_logging';
    private static $loggerCache = [];

    public static function get(string $name = 'system', string $group = 'default')
    {
        $cacheKey = "{$name}.{$group}";
        if (!isset(self::$loggerCache[$cacheKey])) {
            $logger = ApplicationContext::getContainer()
                ->get(LoggerFactory::class)
                ->get($name, $group);
            self::tuneMonologLoopDetection($logger);
            self::$loggerCache[$cacheKey] = $logger;
        }
        return self::$loggerCache[$cacheKey];
    }

    /**
     * 對外公開，業務專案使用 → message channel (service.log)
     */
    public static function info(string $message, $data = [])
    {
        self::writeLog('info', $message, $data, 'message');
    }

    public static function error(string $message, $data = [])
    {
        self::writeLog('error', $message, $data, 'message');
    }

    /**
     * package 內部專用 → default channel (system.log)
     */
    public static function internalInfo(string $message, $data = [])
    {
        self::writeLog('info', $message, $data, 'internal');
    }

    public static function internalError(string $message, $data = [])
    {
        self::writeLog('error', $message, $data, 'internal');
    }

    /**
     * 共用的日誌寫入邏輯
     */
    private static function writeLog(string $level, string $message, $data = [], string $channel = 'message')
    {
        if (self::isLoggingInProgress()) {
            error_log("Log Loop Detected! Level: {$level}, Message: {$message}");
            return;
        }

        self::setLoggingGuard(true);

        try {
            $value = is_array($data) ? $data : ['data' => $data];

            if ($channel === 'message') {
                $log = self::get('message', 'message');
            } else {
                $log = self::get('system', 'default');
            }

            $log->{$level}($message, $value);
        } catch (\Throwable $e) {
            error_log("Logging failed in Log Helper: " . $e->getMessage());
        } finally {
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

        if (!method_exists(\Swoole\Coroutine::class, 'getCid') ||
            !method_exists(\Swoole\Coroutine::class, 'getContext')) {
            return null;
        }

        if (\Swoole\Coroutine::getCid() <= 0) {
            return null;
        }

        return \Swoole\Coroutine::getContext();
    }

    private static function tuneMonologLoopDetection($logger): void
    {
        if (self::getCoroutineContext() === null) {
            return;
        }

        try {
            $target = $logger;

            if (!($target instanceof \Monolog\Logger)) {
                $ref = new \ReflectionObject($logger);
                foreach ($ref->getProperties() as $prop) {
                    $prop->setAccessible(true);
                    $val = $prop->getValue($logger);
                    if ($val instanceof \Monolog\Logger) {
                        $target = $val;
                        break;
                    }
                }
            }

            if ($target instanceof \Monolog\Logger &&
                method_exists($target, 'useLoggingLoopDetection')) {
                $target->useLoggingLoopDetection(false);
            }
        } catch (\Throwable $e) {
            // silent fail
        }
    }
}