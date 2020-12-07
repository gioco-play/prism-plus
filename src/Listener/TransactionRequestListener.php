<?php
declare(strict_types=1);

namespace App\Listener;

use GiocoPlus\PrismPlus\Event\SeamlessRequest;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 交易請求監聽
 * Class SeamlessRequestListener
 * @package GiocoPlus\PrismPlus\Listener
 */
class TransactionRequestListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
             SeamlessRequest::class,
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event)
    {
        var_dump($event->response);
    }
}