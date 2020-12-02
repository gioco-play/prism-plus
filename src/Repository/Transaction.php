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

    protected $operatorCode;

    protected $playerName;

    protected $walletCode;

    /**
     * Transaction constructor.
     * @param string $operatorCode
     * @param string $playerName
     * @param string $walletCode
     */
    public function __construct(string $operatorCode, string $playerName, string $walletCode) {
        $this->postgres = $this->dbManager->opPostgreDb($operatorCode);
        $this->operatorCode = $operatorCode;
        $this->playerName = $playerName;
        $this->walletCode = $walletCode;
    }

    public function transferIn(string $transType, float $amount, string $traceId) {

    }

    public function transferOut(string $transType, float $amount, string $traceId) {

    }

    public function forceTransferOut(string $transType, float $amount, string $traceId) {

    }

    protected function transaction(string $transType, float $amount, string $traceId, bool $force = false) {

    }

    protected function transactionLog(string $transType, string $vendorCode, string $uniqId, string $playerName, string $walletCode,
                                          float $balance, float $amount) {
        return [
            'trans_type' => $transType,
            'player_name' => $playerName,
            'wallet_code' => $walletCode,
            'before_balance' => $balance,
            'amount' => $amount,
            'balance' => bcsub(strval($balance), strval($amount), 2),
            'trace_id' => gen_trace_id($playerName, $vendorCode, $transType, $uniqId),
            'created_time' => micro_timestamp(),
            'belong_date' => date('Y-m-d')
        ];
    }
}