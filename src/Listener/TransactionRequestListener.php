<?php
declare(strict_types=1);

namespace App\Listener;

use GiocoPlus\PrismPlus\Event\TransactionErrorRequest;
use GiocoPlus\PrismPlus\Event\TransactionSuccessRequest;
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
            TransactionErrorRequest::class,
            TransactionSuccessRequest::class
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event)
    {
        switch (get_class($event)) {
            case TransactionSuccessRequest::class :
                var_dump($event->response);
                break;
            case TransactionErrorRequest::class :
                var_dump($event->response);
                break;
        }
    }
}