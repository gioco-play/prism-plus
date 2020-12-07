<?php
declare(strict_types=1);

namespace App\Listener;

use GiocoPlus\PrismPlus\Event\SeamlessRequest;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 類單一請求監聽
 * Class SeamlessRequestListener
 * @package GiocoPlus\PrismPlus\Listener
 */
class SeamlessRequestListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
             SeamlessRequest::class,
        ];
    }

    /**
     * @param SeamlessRequest $event
     */
    public function process(object $event)
    {
        var_dump($event->response);
    }
}