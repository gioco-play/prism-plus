<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus;


class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                CacheInterface::class => Cache::class,
                ConfigInterface::class => Config::class,
                EventDispatcherInterface::class => EventDispatcher::class
            ],
            'commands' => [
            ],
            'listeners' => [

            ],
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'scan' => [
                'paths' => [
                    __DIR__,
                ],
            ],
            'publish' => [
                [
                    'id' => 'VendorRequestListener',
                    'description' => 'VendorRequestListener',
                    'source' => __DIR__ . '/Listener/VendorRequestListener.php',
                    'destination' => BASE_PATH . '/app/Listener/VendorRequestListener.php',
                ],
                [
                    'id' => 'OrderTimeoutRequest',
                    'description' => 'OrderTimeoutRequest',
                    'source' => __DIR__ . '/Event/OrderTimeoutRequest.php',
                    'destination' => BASE_PATH . '/app/Event/OrderTimeoutRequest.php',
                ],
                [
                    'id' => 'connectionPool',
                    'description' => 'The config of connectionPool.',
                    'source' => __DIR__ . '/publish/connection_pool.php',
                    'destination' => BASE_PATH . '/config/autoload/connection_pool.php',
                ],
                /*
                [
                    'id' => 'BoCheckerMiddleware',
                    'description' => 'The bo ip checker',
                    'source' => __DIR__ . '/Middleware/Bo/CheckerMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/Bo/CheckerMiddleware.php',
                ],
                [
                    'id' => 'PermissionCheckerMiddleware',
                    'description' => 'The bo permission checker',
                    'source' => __DIR__ . '/Middleware/Bo/PermissionCheckerMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/Bo/PermissionCheckerMiddleware.php',
                ],
                [
                    'id' => 'SwitchCheckerMiddleware',
                    'description' => 'The bo status checker',
                    'source' => __DIR__ . '/Middleware/Bo/SwitchCheckerMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/Bo/SwitchCheckerMiddleware.php',
                ],
                [
                    'id' => 'OperatorCheckerMiddleware',
                    'description' => 'The operator checker',
                    'source' => __DIR__ . '/Middleware/Operator/CheckerMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/Operator/CheckerMiddleware.php',
                ],
                [
                    'id' => 'VendorCheckerMiddleware',
                    'description' => 'The vendor ip checker',
                    'source' => __DIR__ . '/Middleware/Vendor/CheckerMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/Vendor/CheckerMiddleware.php',
                ],
                [
                    'id' => 'GlobalIPBlockMiddleware',
                    'description' => 'The global ip block checker',
                    'source' => __DIR__ . '/Middleware/GlobalIPBlockMiddleware.php',
                    'destination' => BASE_PATH . '/app/Middleware/GlobalIPBlockMiddleware.php',
                ]
                */
            ],
        ];
    }
}
