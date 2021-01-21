<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Event;


/**
 * Class OrderTimeoutRequest
 * @package App\Event
 */
class OrderTimeoutRequest
{

    /**
     * @var array
     */
    public $orderData;

    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }
}