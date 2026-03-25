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
    // 防止巢狀資料過深導致記憶體暴增
    private const MAX_SANITIZE_DEPTH = 6;
    // 單一字串最大保留長度，避免 log 被超長內容淹沒
    private const MAX_STRING_LENGTH = 2000;
    // Response body 僅預覽前 N bytes，避免整包載入
    private const MAX_RESPONSE_BODY_PREVIEW = 2048;

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
        if (self::isLoggingInProgress()) {
            error_log("Log Loop Detected! Level: {$level}, Message: {$message}");
            return;
        }

        self::setLoggingGuard(true);

        try {
            $value = is_array($data)
                ? self::sanitize($data)
                : ['data' => self::sanitizeAny($data)];

            $log = self::get('message', 'message');
            $log->{$level}($message, $value);
        } catch (\Throwable $e) {
            error_log("Logging failed: " . $e->getMessage());
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

        if (!method_exists(\Swoole\Coroutine::class, 'getCid') || !method_exists(\Swoole\Coroutine::class, 'getContext')) {
            return null;
        }

        if (\Swoole\Coroutine::getCid() <= 0) {
            return null;
        }

        return \Swoole\Coroutine::getContext();
    }

    private static function sanitize(array $data): array
    {
        // 用 ReflectionReference id 追蹤陣列參照，避免循環參照遞迴
        $seenRefs = [];
        return self::sanitizeArray($data, 0, $seenRefs);
    }

    private static function sanitizeAny($value)
    {
        $seenRefs = [];
        return self::sanitizeValue($value, 0, $seenRefs);
    }

    private static function sanitizeArray(array $data, int $depth, array &$seenRefs): array
    {
        if ($depth >= self::MAX_SANITIZE_DEPTH) {
            return ['__truncated' => sprintf('max depth %d reached', self::MAX_SANITIZE_DEPTH)];
        }

        $result = [];
        foreach ($data as $key => $value) {
            $refId = self::getArrayReferenceId($data, $key);
            if ($refId !== null) {
                if (isset($seenRefs[$refId])) {
                    $result[$key] = '[circular_reference]';
                    continue;
                }
                $seenRefs[$refId] = true;
            }

            $result[$key] = self::sanitizeValue($value, $depth + 1, $seenRefs);

            if ($refId !== null) {
                unset($seenRefs[$refId]);
            }
        }

        return $result;
    }

    private static function sanitizeValue($value, int $depth, array &$seenRefs)
    {
        if ($depth > self::MAX_SANITIZE_DEPTH) {
            return '[max_depth_reached]';
        }

        if ($value instanceof \Throwable) {
            return [
                'message' => $value->getMessage(),
                'code'    => $value->getCode(),
                'file'    => $value->getFile() . ':' . $value->getLine(),
                'trace'   => self::truncateString($value->getTraceAsString()),
            ];
        }

        if ($value instanceof \Psr\Http\Message\ResponseInterface) {
            return self::sanitizeResponse($value);
        }

        if ($value instanceof \Psr\Http\Message\RequestInterface) {
            return [
                'method' => $value->getMethod(),
                'uri'    => (string) $value->getUri(),
            ];
        }

        if (is_object($value)) {
            $normalized = ['class' => get_class($value)];
            if (method_exists($value, '__toString')) {
                try {
                    $normalized['value'] = self::truncateString((string) $value);
                } catch (\Throwable $e) {
                    $normalized['value'] = '[toString_failed]';
                }
            }
            return $normalized;
        }

        if (is_array($value)) {
            return self::sanitizeArray($value, $depth, $seenRefs);
        }

        if (is_string($value)) {
            return self::truncateString($value);
        }

        return $value;
    }

    private static function sanitizeResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        // 只取 preview，避免直接 (string)$body 造成記憶體與副作用風險
        $body = self::readStreamPreview($response->getBody());

        return [
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
            'body_size' => $body['size'],
            'body_preview' => $body['preview'],
        ];
    }

    private static function readStreamPreview(\Psr\Http\Message\StreamInterface $stream): array
    {
        $size = $stream->getSize();
        if (!$stream->isSeekable()) {
            return [
                'size' => $size,
                'preview' => '[non-seekable stream omitted]',
            ];
        }

        try {
            // 先記錄原位置，讀完 preview 後再 seek 回去，避免影響後續流程
            $currentPosition = $stream->tell();
            $stream->rewind();
            $preview = $stream->read(self::MAX_RESPONSE_BODY_PREVIEW);
            $hasMore = !$stream->eof();
            $stream->seek($currentPosition);
        } catch (\Throwable $e) {
            return [
                'size' => $size,
                'preview' => '[stream preview unavailable]',
            ];
        }

        if ($hasMore) {
            $preview .= '...[truncated]';
        }

        return [
            'size' => $size,
            'preview' => self::truncateString($preview, self::MAX_RESPONSE_BODY_PREVIEW + 32),
        ];
    }

    private static function getArrayReferenceId(array $data, $key): ?string
    {
        if (!class_exists(\ReflectionReference::class)) {
            return null;
        }

        $reference = \ReflectionReference::fromArrayElement($data, $key);
        if ($reference === null) {
            return null;
        }

        return bin2hex($reference->getId());
    }

    private static function truncateString(string $value, int $maxLength = self::MAX_STRING_LENGTH): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength) . '...[truncated]';
    }
}
