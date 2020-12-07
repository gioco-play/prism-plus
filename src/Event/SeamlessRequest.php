<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Event;

use GuzzleHttp\TransferStats;

/**
 * 類單一請求事件
 * Class SeamlessRequest
 * @package GiocoPlus\EZAdmin\Event
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