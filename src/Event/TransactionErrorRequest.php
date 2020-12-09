<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Event;


/**
 * 交易請求事件
 * Class SeamlessRequest
 * @package GiocoPlus\PrismPlus\Event
 */
class TransactionErrorRequest
{

    public $response;

    public function __construct($response) {
        $this->response  = $response;
    }

}