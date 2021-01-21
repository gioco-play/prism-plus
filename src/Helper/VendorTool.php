<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Helper;


use GiocoPlus\PrismConst\State\ProductState;
use GiocoPlus\PrismPlus\Repository\DbManager;
use GiocoPlus\PrismPlus\Service\OperatorCacheService;
use GiocoPlus\PrismPlus\Service\VendorCacheService;
use Hyperf\Di\Annotation\Inject;
use function Symfony\Component\Translation\t;

class VendorTool
{
    /**
     * @Inject()
     * @var VendorCacheService
     */
    private $vendorCache;

    /**
     * @Inject()
     * @var OperatorCacheService
     */
    private $opCache;

    /**
     * @Inject()
     * @var DbManager
     */
    private $dbManager;

    /**
     * Vendor語系對應
     * @param string $vendorCode
     * @param string $lang
     * @param string $defaultLang
     * @return string
     */
    public function languageMapping(string $vendorCode, string $lang, string $defaultLang):string {
        $languages = $this->vendorCache->language(strtolower($vendorCode));
        return $languages[strtolower($lang)] ?? $defaultLang;
    }

    /**
     * Game語系對應
     * @param string $gameCode
     * @param string $lang
     * @param string $defaultLang
     * @return string
     */
    public function gameLanguageMapping( string $gameCode, string $lang, string $defaultLang):string {
        $languages = json_decode(json_encode($this->vendorCache->game($gameCode)['language']??[]), true);
        return $languages[strtolower($lang)] ?? $defaultLang;
    }

    /**
     * Vendor幣別對應
     * @param string $vendorCode
     * @param string $currency
     * @return string
     */
    public function currencyMapping(string $vendorCode, string $currency): string {
        $currencies = $this->vendorCache->currency(strtolower($vendorCode));
        if (isset($currencies[strtolower($currency)]) == false) {
            throw new \Exception(ProductState::CURRENCY_NOT_EXIST['msg']."[{$currency}]", ProductState::CURRENCY_NOT_EXIST['code']);
        }
        return $currencies[strtolower($currency)];
    }

    /**
     * Game幣別對應
     * @param string $gameCode
     * @param string $currency
     * @return string
     */
    public function gameCurrencyMapping(string $gameCode, string $currency): string {
        $currencies = json_decode(json_encode($this->vendorCache->game($gameCode)['currency']??[]), true);
        if (isset($currencies[strtolower($currency)]) == false) {
            throw new \Exception(ProductState::GAME_CURRENCY_NOT_EXIST['msg']."[{$currency}]", ProductState::GAME_CURRENCY_NOT_EXIST['code']);
        }
        return $currencies[strtolower($currency)];
    }

    /**
     * 玩家遊戲註冊
     * @param string $opCode
     * @param string $vendorCode
     * @param string $account
     * @param bool $removeLog
     * @throws \GiocoPlus\Mongodb\Exception\MongoDBException
     */
    public function playerGameRegister(string $opCode, string $vendorCode, string $account, bool $removeLog = false) {
        $vendorCode = strtolower($vendorCode);
        $register = $this->dbManager->opMongoDb($opCode)->fetchAll('player_game_register', ['vendor' => $vendorCode, 'account' => $account]);

        if ($removeLog === true) {
            $this->dbManager->opMongoDb($opCode)->delete('player_game_register', ['vendor' => $vendorCode, 'account' => $account]);
            return false;
        }

        if ($removeLog === false && current($register) === false) {
            $this->dbManager->opMongoDb($opCode)->insert('player_game_register', ['vendor' => $vendorCode, 'account' => $account]);
            return false;
        }

        return current($register) ? true : false;
    }
}