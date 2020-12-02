<?php
declare(strict_types=1);

namespace GiocoPlus\EZAdmin\Repository;

/**
 * 交易類別對照
 * Class TransactionConst
 * @package GiocoPlus\EZAdmin\Repository
 */
class TransactionConst
{
    /**
     * 下注
     */
    const Stake = 'stake';

    /**
     * 派彩
     */
    const Payoff = 'payoff';

    /**
     * 上分遊戲場館
     */
    const GameTransferIn = 'game_transfer_in';

    /**
     * 下分遊戲場館
     */
    const GameTransferOut = 'game_transfer_out';

    /**
     * 上分GF平台錢包
     */
    const TransferIn = 'transfer_in';

    /**
     * 下分GF平台錢包
     */
    const TransferOut = 'transfer_out';

    /**
     * 取消下注
     */
    const CancelStake = 'cancel_stake';

    /**
     * 取消派彩
     */
    const CancelPayoff = 'cancel_payoff';

    /**
     * 錢包之間 - 轉入
     */
    const WalletTransferIn = 'wallet_transfer_in';

    /**
     * 錢包之間 - 轉出
     */
    const WalletTransferOut = 'wallet_transfer_out';
}