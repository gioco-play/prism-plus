<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Exception;

/**
 * Class TransactionException
 * @package GiocoPlus\EZAdmin\Exception
 */
class TransactionException extends \RuntimeException
{
    /**
     * TransactionException constructor.
     * @param array $error
     * @param $extraMsg
     */
    public function __construct(array $error, $extraMsg = null) {
        $msg = $extraMsg ? $error['msg']."[$extraMsg]" : $error['msg'];
        $this->code  = $error['code'];
        $this->message  = $msg;
    }
}