<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\OrderTimeoutRequest;
use GiocoPlus\Mongodb\MongoDb;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 遊戲商請求
 * Class VendorRequestListener
 * @package App\Listener
 */
class VendorRequestListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @Inject()
     * @var MongoDb
     */
    private $mongodb;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            OrderTimeoutRequest::class
        ];
    }

    /**
     * @param OrderTimeoutRequest $event
     */
    public function process(object $event)
    {
        co(function () use ($event) {
            $this->mongodb->setPool('default')->insert("transaction_timeout", $event->orderData);
        });
    }
}
