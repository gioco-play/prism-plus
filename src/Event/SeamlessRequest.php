<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Event;

use GuzzleHttp\TransferStats;

/**
 * 類單一請求事件
 * Class SeamlessRequest
 * @package GiocoPlus\PrismPlus\Event
 */
class SeamlessRequest
{
    /**
     * @var TransferStats
     */
    public $response;

    public function __construct(TransferStats $response) {
        $this->response  = $response;
    }

}