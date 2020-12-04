<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Event;

/**
 * 類單一請求事件
 * Class SeamlessRequest
 * @package GiocoPlus\EZAdmin\Event
 */
class SeamlessRequest
{
    public $response;

    public function __construct($response) {
        $this->response  = $response;
    }

}