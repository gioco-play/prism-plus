<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Repository;

use GiocoPlus\PrismPlus\Event\TransactionRequest;
use GiocoPlus\PrismPlus\Exception\TransactionException;
use GiocoPlus\PrismPlus\Helper\ApiResponse;
use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Repository\Traits\SeamlessTrait;
use GiocoPlus\PrismPlus\Service\CacheService;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 交易
 *
 * 注意： 轉帳模式：幣值轉換只有在商戶 "上分"、"下分"
 *
 * Class Transaction
 * @package GiocoPlus\PrismPlus\Repository
 */
class Transaction
{

    use SeamlessTrait;

    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;


    /**
     * @Inject
     * @var DbManager
     */
    protected $dbManager;

    /**
     * @Inject
     * @var CacheService
     */
    protected $cache;

    /**
     * @var \Swoole\Coroutine\PostgreSQL
     */
    protected $postgres;

    /**
     * 商戶資訊
     * @var \stdClass
     */
    protected $operator;

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
     * 幣值轉換值
     * @var float
     */
    protected $currencyRate;

    /**
     * 錢包精度
     * @var int
     */
    protected $currencyScale = 2;

    /**
     * 遊戲商代碼
     * @var string
     */
    protected $vendorCode;

    /**
     * 類單一錢包
     * @var bool
     */
    protected $seamlessEnable = false;

    /**
     * 類單一配置
     * @var null
     */
    protected $seamlessSetting = null;

    /**
     * Transaction constructor.
     * @param string $accountOp (玩家帳號含商戶代碼)
     * @param string $walletCode
     */
    public function __construct(string $accountOp, string $walletCode) {

        list($this->playerName, $this->operatorCode) = array_values(Tool::MemberSplitCode($accountOp));
        $this->postgres = $this->dbManager->opPostgreDb(strtolower($this->operatorCode));
        $this->operator = (array) $this->cache->operator($this->operatorCode);
        $this->walletCode = $walletCode;
        // 根據錢包代碼取得產品商代碼
        list($gf, $vendorCode, $wallet) = explode('_', $walletCode);
        // 判斷產品是否啟用
        if (isset($this->operator['vendor_switch']->$vendorCode) === false) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::PRODUCT_NEED_ACK,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::PRODUCT_NEED_ACK, $vendorCode);
        }
        // 判斷幣值轉換
        $currencyRates = collect($this->operator['currency_rate'])->pluck('rate', 'vendor');
        if (isset($currencyRates[$vendorCode]) === false) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_CURRENCY_RATE_EMPTY,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_CURRENCY_RATE_EMPTY, $walletCode);
        }
        $this->vendorCode = $vendorCode;
        $this->currencyRate = $currencyRates[$vendorCode];
        // 檢查類單一配置
        $this->seamlessEnable = $this->operator['vendor_switch']->$vendorCode->seamless_enable ?? false;

        if ($this->seamlessEnable) {
            if (isset($this->operator['seamless_setting']) === false || empty($this->operator['seamless_setting']->host)) {
                $this->eventDispatcher(new TransactionRequest([
                    'error' => ApiResponse::TRANS_SEAMLESS_ERROR,
                    'func' => __FUNCTION__,
                    'args' => func_get_args()
                ]));
                throw new TransactionException(ApiResponse::TRANS_SEAMLESS_ERROR);
            }
            $this->seamlessSetting = $this->operator['seamless_setting'];
        }
        // 錢包初始化
        if ($this->seamlessEnable === false) {
            $result = $this->walletInit();
            if ($result === false) {
                $this->eventDispatcher(new TransactionRequest([
                    'error' => ApiResponse::TRANS_WALLET_EMPTY,
                    'func' => __FUNCTION__,
                    'args' => func_get_args()
                ]));
                throw new TransactionException(ApiResponse::TRANS_WALLET_EMPTY, $walletCode);
            }
        }
    }

    /**
     * 商戶 - 上分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    public function opTransferIn(float $amount, string $traceId) {
        if ($amount <= 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }
        // 幣值轉換
        $amount = $this->exchangeRate($amount, '/');

        $result = $this->transaction(TransactionConst::TRANSFER_IN, $amount, $traceId);
        if ($result) {
            return $this->formatReturn($result);
        }

        return false;
    }

    /**
     * 商戶 - 下分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    public function opTransferOut(float $amount, string $traceId) {
        if ($amount <= 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        // 幣值轉換
        $amount = $this->exchangeRate(-1 * $amount, '*');

        $result = $this->transaction(TransactionConst::TRANSFER_OUT, $amount, $traceId);
        if ($result) {
            return $this->formatReturn($result);
        }

        return false;
    }

    /**
     * 遊戲場館 - 上分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    public function gameTransferIn(float $amount, string $traceId) {
        if ($amount <= 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        $amount = -1 * $amount;

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGameTransferIn($amount, $traceId);
        }

        $result = $this->transaction(TransactionConst::GAME_TRANSFER_IN, $amount, $traceId);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 遊戲場館 - 下分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    public function gameTransferOut(float $amount, string $traceId) {
        if ($amount < 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGameTransferOut($amount, $traceId);
        }

        $result = $this->transaction(TransactionConst::GAME_TRANSFER_OUT, $amount, $traceId);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 下注 - 老虎機
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    public function gameStake(float $amount, string $traceId, string $betId) {
        if ($amount < 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        $amount = -1 * $amount;

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGameStake($amount, $traceId, $betId);
        }

        $result = $this->transaction(TransactionConst::STAKE, $amount, $traceId, $betId);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 派彩 - 老虎機
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    public function gamePayoff(float $amount, string $traceId, string $betId) {
        if ($amount < 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGamePayoff($amount, $traceId, $betId);
        }

        $result = $this->transaction(TransactionConst::PAYOFF, $amount, $traceId, $betId);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 取消下注
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    public function gameCancelStake(float $amount, string $traceId, string $betId) {
        if ($amount <= 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGameCancelStake($amount, $traceId, $betId);
        }

        $result = $this->transaction(TransactionConst::CANCEL_STAKE, $amount, $traceId, $betId, true);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 取消派彩
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    public function gameCancelPayoff(float $amount, string $traceId, string $betId) {
        if ($amount <= 0) {
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_AMOUNT_ERROR,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_AMOUNT_ERROR, $amount);
        }

        $amount = -1 * $amount;

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGamePayoff($amount, $traceId, $betId);
        }

        $result = $this->transaction(TransactionConst::CANCEL_PAYOFF, $amount, $traceId, $betId, true);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 調整玩家錢包 （活動派彩）
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    public function adjust(float $amount, string $traceId) {

        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessAdjust($amount, $traceId);
        }

        $result = $this->transaction(TransactionConst::ADJUST, $amount, $traceId, null, true);
        if ($result) {
            return $this->formatReturn($result);
        }
        return false;
    }

    /**
     * 取得錢包餘額
     */
    public function getBalance() {
        // 類單一
        if ($this->seamlessEnable) {
            return $this->seamlessGetBalance();
        }

        $pg = $this->postgres;
        $result = $pg->query("SELECT * FROM member_wallets WHERE player_name='{$this->playerName}' AND wallet_code = '{$this->walletCode}'");
        if ($result) {
            return current($pg->fetchAll($result));
        }
        return false;
    }

    /**
     * 錢包交易
     * @param string $transType
     * @param float $amount
     * @param string $traceId
     * @param string|null $betId
     * @param bool $force
     * @return false
     */
    protected function transaction(string $transType, float $amount, string $traceId, string $betId = null, bool $force = false) {
        $pg = $this->postgres;
        try {
            $pg->query("BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ;");
            $result = $pg->query("SELECT * FROM member_wallets WHERE player_name='{$this->playerName}' AND wallet_code = '{$this->walletCode}' FOR UPDATE;");
            if ($result) {
                $wallet = current($pg->fetchAll($result));
                $beforeBalance = floatval($wallet['balance']);
                $balance =  floatval(bcadd(strval($beforeBalance), strval($amount), $this->currencyScale));
                if ($force === false && $balance < 0 && $transType == TransactionConst::TransferOut) {
                    $this->eventDispatcher(new TransactionRequest([
                        'error' => ApiResponse::TRANS_BALANCE_SHORT,
                        'func' => __FUNCTION__,
                        'args' => func_get_args()
                    ]));
                    throw new \Exception(ApiResponse::TRANS_BALANCE_SHORT['msg'], ApiResponse::TRANS_BALANCE_SHORT['code']);
                }
                // 產生交易紀錄
                $transLog = $this->transactionLog(
                    $transType,
                    $traceId,
                    $this->playerName,
                    $this->walletCode,
                    $beforeBalance,
                    $amount,
                    $balance,
                    $betId
                );
                $fields = implode(",", array_keys($transLog));
                $values = "'".implode("','", array_values($transLog))."'";
                $transResult = $pg->query("INSERT INTO transactions ($fields) VALUES ($values) RETURNING trace_id ;");
                // 更新錢包餘額
                $time = micro_timestamp();
                $walletResult = $pg->query("UPDATE member_wallets SET balance = balance + {$amount}, updated_at = {$time} WHERE player_name='{$this->playerName}' 
                                    AND wallet_code = '{$this->walletCode}' RETURNING player_name,wallet_code,balance,trans_type,trace_id ");
                if ($transResult && $walletResult) {
                    $pg->query("COMMIT;");
                    return $pg->fetchRow($walletResult);
                }
                $pg->query("ROLLBACK;");
                return false;
            }
        } catch (\Exception $e) {
            $pg->query("ROLLBACK;");
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_BALANCE_FAIL,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_BALANCE_FAIL);
        }
        return false;
    }

    /**
     * 錢包初始化
     */
    protected function walletInit() {
        $pg = $this->postgres;
        try {
            $pg->query("BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ;");
            $result = $pg->query("SELECT * FROM member_wallets WHERE player_name='{$this->playerName}' AND wallet_code = '{$this->walletCode}'");
            if ($result === false) {
                $form = [
                    'player_name' => $this->playerName,
                    'wallet_code' => $this->walletCode,
                    'balance' => 0,
                    'created_at' => micro_timestamp(),
                    'updated_at' => micro_timestamp()
                ];
                $fields = implode(",", array_keys($form));
                $values = "'".implode("','", array_values($form))."'";
                $result = $pg->query("INSERT INTO member_wallets ($fields) VALUES ($values) RETURNING wallet_code ;");
                if ($result) {
                    $pg->query("COMMIT;");
                    return true;
                }
            }
            $pg->query("ROLLBACK;");
            return true;
        } catch (\Exception $e) {
            $pg->query("ROLLBACK;");
            $this->eventDispatcher(new TransactionRequest([
                'error' => ApiResponse::TRANS_WALLET_INIT_FAIL,
                'func' => __FUNCTION__,
                'args' => func_get_args()
            ]));
            throw new TransactionException(ApiResponse::TRANS_WALLET_INIT_FAIL);
        }
    }

    /**
     * 交易紀錄
     * @param string $transType
     * @param string $traceId
     * @param string $playerName
     * @param string $walletCode
     * @param float $beforeBalance
     * @param float $balance
     * @param float $amount
     * @param string $betId
     * @return array
     */
    protected function transactionLog(string $transType, string $traceId, string $playerName, string $walletCode,
                                          float $beforeBalance, float $amount, float $balance, string $betId) {
        $defParams = [
            'trans_type' => $transType,
            'player_name' => $playerName,
            'wallet_code' => $walletCode,
            'before_balance' => $beforeBalance,
            'amount' => $amount,
            'balance' => $balance,
            'trace_id' => $traceId,
            'created_time' => micro_timestamp(),
            'belong_date' => date('Y-m-d')
        ];
        // 注單號
        if ($betId) {
            $defParams['bet_id'] = $betId;
        }

        return $defParams;
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

    /**
     * 幣值轉換
     * @param float $amount
     * @param string $operator
     * @return false|float
     */
    protected function exchangeRate(float $amount, string $operator) {
        $amount = strval($amount);
        $rate = strval($this->currencyRate);
        switch ($operator) {
            case '*':
                return floatval(bcmul($amount, $rate, $this->currencyScale));
            case '/':
                return floatval(bcdiv($amount, $rate, $this->currencyScale));
            default :
                return  false;
        }
    }
}