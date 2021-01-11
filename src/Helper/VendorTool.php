<?php
declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Helper;


use _HumbugBox221ad6f1b81f\Nette\Neon\Exception;
use GiocoPlus\PrismConst\State\ProductState;
use GiocoPlus\PrismPlus\Service\VendorCacheService;
use Hyperf\Di\Annotation\Inject;

class VendorTool
{
    /**
     * @Inject()
     * @var VendorCacheService
     */
    private $vendorCache;

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
        $languages = json_decode(json_encode($this->vendorCache->game($gameCode)->language??[]), true);
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
            throw new \Exception(ProductState::CURRENCY_NOT_EXIST['msg'], ProductState::CURRENCY_NOT_EXIST['code']);
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
        $currencies = json_decode(json_encode($this->vendorCache->game($gameCode)->currency??[]), true);
        if (isset($currencies[strtolower($currency)]) == false) {
            throw new \Exception(ProductState::GAME_CURRENCY_NOT_EXIST['msg'], ProductState::GAME_CURRENCY_NOT_EXIST['code']);
        }
        return $currencies[strtolower($currency)];
    }
}