<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Repository\Traits;

use GiocoPlus\EZAdmin\Event\SeamlessRequest;
use GiocoPlus\EZAdmin\Repository\TransactionConst;
use GuzzleHttp\TransferStats;
use Hyperf\Guzzle\HandlerStackFactory;
use GuzzleHttp\Client;

/**
 * 類單一錢包
 * Trait SeamlessTrait
 * @package GiocoPlus\EZAdmin\Repository\Traits
 */
trait SeamlessTrait
{
    /**
     * 遊戲場館 - 上分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    private function seamlessGameTransferIn(float $amount, string $traceId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::GAME_TRANSFER_IN,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId
        ];
        return $this->CallPostAPI('/sw/game-transfer-in', $params);
    }

    /**
     * 遊戲場館 - 下分
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    private function seamlessGameTransferOut(float $amount, string $traceId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::GAME_TRANSFER_OUT,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId
        ];
        return $this->CallPostAPI('/sw/game-transfer-out', $params);
    }

    /**
     * 下注 - 老虎機
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    private function seamlessGameStake(float $amount, string $traceId, string $betId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::STAKE,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId,
            'bet_id' => $betId
        ];
        return $this->CallPostAPI('/sw/game-stake', $params);
    }

    /**
     * 派彩 - 老虎機
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    private function seamlessGamePayoff(float $amount, string $traceId, string $betId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::PAYOFF,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId,
            'bet_id' => $betId
        ];
        return $this->CallPostAPI('/sw/game-payoff', $params);
    }

    /**
     * 取消下注
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    private function seamlessGameCancelStake(float $amount, string $traceId, string $betId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::CANCEL_STAKE,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId,
            'bet_id' => $betId
        ];
        return $this->CallPostAPI('/sw/cancel-stake', $params);
    }

    /**
     * 取消派彩
     * @param float $amount
     * @param string $traceId
     * @param string $betId
     * @return array|false
     */
    private function seamlessGameCancelPayoff(float $amount, string $traceId, string $betId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::CANCEL_PAYOFF,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId,
            'bet_id' => $betId
        ];
        return $this->CallPostAPI('/sw/cancel-payoff', $params);
    }

    /**
     * 調整玩家錢包
     * @param float $amount
     * @param string $traceId
     * @return array|false
     */
    private function seamlessAdjust(float $amount, string $traceId) {
        $amount = $this->exchangeRate($amount, '*');
        $params = [
            'trans_type' => TransactionConst::ADJUST,
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode),
            'amount' => $amount,
            'trace_id' => $traceId
        ];
        return $this->CallPostAPI('/sw/adjust-balance', $params);
    }

    /**
     * 取得錢包餘額
     * @return mixed
     */
    private function seamlessGetBalance() {
        $params = [
            'member_account' => $this->playerName,
            'vendor_code' => strtoupper($this->vendorCode)
        ];
        return $this->CallPostAPI('/sw/player-balance', $params);
    }

    /**
     * 查詢交易紀錄（類單一）
     * @param string $traceId
     * @return mixed
     */
    public function queryTransactionLog(string $traceId){
        $params = [
            'member_account' => $this->playerName,
            'trace_id' => $traceId
        ];
        return $this->CallPostAPI('/sw/query-translog', $params);
    }

    /**
     * 呼叫API
     * @param $path
     * @param array $params
     * @return mixed
     */
    protected function CallPostAPI($path, $params = []) {
        $factory = new HandlerStackFactory();
        $stack = $factory->create([
            'max_connections' => config('ezadmin.transaction.seamless.max_connections', 50),
        ], [
            'retries' => config('ezadmin.transaction.seamless.retries', 1),
            'delay' => config('ezadmin.transaction.seamless.delay', 10),
        ]);

        $client = make(Client::class, [
            'config' => [
                'base_uri' => $this->seamlessSetting->host,
                'timeout' => floatval($this->connectTimeout),
                'handler' => $stack,
            ],
        ]);

        return $client->request('POST', $path, [
            'json' => $params,
            'headers' => [
                'Content-Type' => 'application/json',
                'wtoken' => $this->seamlessSetting->wtoken
            ],
            'on_stats' => function(TransferStats $stats) {
                $this->eventDispatcher->dispatch(new SeamlessRequest($stats));
            }
        ]);

    }

}