<?php

declare(strict_types=1);

namespace GiocoPlus\EZAdmin;


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
                    'id' => 'config',
                    'description' => 'The config of ezadmin client.',
                    'source' => __DIR__ . '/publish/ezadmin.php',
                    'destination' => BASE_PATH . '/config/autoload/ezadmin.php',
                ],
            ],
        ];
    }
}
