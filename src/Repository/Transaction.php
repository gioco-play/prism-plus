<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Repository;

use Hyperf\Di\Annotation\Inject;

/**
 * 交易
 * Class Transaction
 * @package GiocoPlus\EZAdmin\Repository
 */
class Transaction
{

    /**
     * @Inject
     * @var DbManager
     */
    protected $dbManager;

    /**
     * @var \Swoole\Coroutine\PostgreSQL
     */
    protected $postgres;

    /**
     * Transaction constructor.
     * @param string $opCode
     */
    public function __construct(string $opCode) {
        $this->postgres = $this->dbManager->opPostgreDb($opCode);
    }

//    public function transferIn($am)
}