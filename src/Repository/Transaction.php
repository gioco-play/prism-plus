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
     * 商戶代碼
     * @var string
     */
    protected $operatorCode;

    /**
     * 玩家帳號
     * @var string
     */
    protected $playerName;

    /**
     * 錢包代碼
     * @var string
     */
    protected $walletCode;

    /**
     * 錢包
     * @var array|bool
     */
    protected $wallet;

    /**
     * 錢包精度
     * @var int
     */
    protected $currencyScale = 2;

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
        $this->wallet = $this->getWallet();
    }

    /**
     * 上分 / 派彩
     * @param string $transType
     * @param float $amount
     * @param string $traceId
     * @param string|null $betId
     * @return array|false
     */
    public function transferIn(string $transType, float $amount, string $traceId, string $betId = null) {
        $amount = floatval($amount);

        // 交易金額必須 >= 0
        if ($amount < 0 ) {
            return false;
        }

        if ($this->wallet) {
            $result = $this->transaction($transType, $amount, $traceId, $betId);
            if ($result) {
                return $this->formatReturn($result);
            }
        }
        return false;
    }

    /**
     * 下分 / 投注
     * @param string $transType
     * @param float $amount
     * @param string $traceId
     * @param string|null $betId
     * @return array|false
     */
    public function transferOut(string $transType, float $amount, string $traceId, string $betId = null) {
        $amount = floatval($amount);
        // 交易金額必須 >= 0
        if ($amount < 0 ) {
            return false;
        }

        if ($this->wallet) {
            // 餘額不足
            if ($amount > floatval($this->wallet['balance'])) {
                return false;
            }
            $result = $this->transaction($transType, $amount, $traceId, $betId);
            if ($result) {
                return $this->formatReturn($result);
            }
        }
        return false;
    }

    /**
     * 注單改派
     * @param string $transType
     * @param float $amount
     * @param string $traceId
     * @param string|null $betId
     * @return array|false
     */
    public function forceTransferOut(string $transType, float $amount, string $traceId, string $betId = null) {
        $amount = floatval($amount);
        // 交易金額必須 >= 0
        if ($amount < 0 ) {
            return false;
        }
        if ($this->wallet) {
            $result = $this->transaction($transType, -1 * $amount, $traceId, $betId, true);
            if ($result) {
                return $this->formatReturn($result);
            }
        }
        return false;
    }

    /**
     * 錢包交易
     * @param string $transType
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @param bool $force
     * @return false
     */
    protected function transaction(string $transType, float $amount, string $traceId, string $betId, bool $force = false) {
        $pg = $this->dbManager->opPostgreDb("gf");
        try {
            $pg->query("BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ;");
            $result = $pg->query("SELECT * FROM member_wallets WHERE player_name='{$this->playerName}' AND wallet_code = '{$this->walletCode}' FOR UPDATE;");
            if ($result) {
                $wallet = current($pg->fetchAll($result));
                $beforeBalance = floatval($wallet['balance']);
                $balance =  bcadd(strval($beforeBalance), strval($amount), $this->currencyScale);

                if ($force === false && $balance < 0 && $transType == TransactionConst::TransferOut) {
                    throw new \Exception("餘額不足", 9001);
                }
                // 產生交易紀錄
                $transLog = $this->transactionLog(
                    $transType,
                    $traceId,
                    $this->playerName,
                    $this->walletCode,
                    $beforeBalance,
                    $amount,
                    $betId
                );
                $fields = implode(",", array_keys($transLog));
                $values = "'".implode("','", array_values($transLog))."'";
                $pg->query("INSERT INTO transactions ($fields) VALUES ($values) RETURNING trace_id ;");
                // 更新錢包餘額
                $result = $pg->query("UPDATE member_wallets SET balance = balance + {$amount} WHERE player_name='{$this->playerName}' 
                                    AND wallet_code = '{$this->walletCode}' RETURNING player_name,wallet_code,balance,trans_type,trace_id ");
                if ($result) {
                    $pg->query("COMMIT;");
                    return $pg->fetchRow($result);
                }
                $pg->query("ROLLBACK;");
                return false;
            }
        } catch (\Exception $e) {
            sprintf('Transaction %s[%s] in %s', $e->getMessage(), $e->getLine(), $e->getFile());
            $pg->query("ROLLBACK;");
        }
        return false;
    }

    /**
     * 交易紀錄
     * @param string $transType
     * @param string $traceId
     * @param string $playerName
     * @param string $walletCode
     * @param float $balance
     * @param float $amount
     * @param string $betId
     * @return array
     */
    protected function transactionLog(string $transType, string $traceId, string $playerName, string $walletCode,
                                          float $balance, float $amount, string $betId) {
        $defParams = [
            'trans_type' => $transType,
            'player_name' => $playerName,
            'wallet_code' => $walletCode,
            'before_balance' => $balance,
            'amount' => $amount,
            'balance' => bcadd(strval($balance), strval($amount), 2),
            'trace_id' => $traceId,
            'created_time' => micro_timestamp(),
            'belong_date' => date('Y-m-d')
        ];

        if ($betId) {
            $defParams['bet_id'] = $betId;
        }

        return $defParams;
    }

    /**
     * 取得錢包
     * @return mixed
     */
    protected function getWallet() {
        $pg = $this->dbManager->opPostgreDb($this->operatorCode);
        return $pg->query("SELECT * FROM member_wallets WHERE player_name='{$this->playerName}' AND wallet_code = '{$this->walletCode}' FOR UPDATE;");
    }

    /**
     * 格式化返回結果
     * @param $result
     * @return array
     */
    protected function formatReturn($result) {
        return [
            'player_name'   => $result[0],
            'wallet_code'   => $result[1],
            'balance'       => floatval($result[2]),
            'trans_type'    => $result[3],
            'trace_id'      => $result[4]
        ];
    }
}