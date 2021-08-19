<?php

declare(strict_types=1);

namespace GiocoPlus\PrismPlus\Helper;

use GiocoPlus\PrismConst\State\ApiState;
use GiocoPlus\PrismConst\Tool\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

/**
 * Class BetlogValidation
 * @package App\Helper
 */
class BetlogValidation
{
    /**
     * @Inject()
     * @var ValidatorFactoryInterface
     */
    protected $validation;

    /**
     * 大表驗證必填欄位
     *
     * @param array $requireInputs
     * @return array
     */
    function betlogs(array $requireInputs): array
    {
        $inputs = $requireInputs;
        $validator = $this->validation->make(
            $inputs,
            [
                'player_name' => 'required|string',
                'member_code' => 'required|string',
                'vendor_code' => 'required|string',
                'game_code' => 'required|string',
                'game_type' => 'required|string',
                'bet_amount' => 'required|numeric',
                'win_amount' => 'required|numeric',
                'wallet_code' => 'required|string',
                'game_time' => 'required|min:13|max:13',
                'trans_type' => 'required|string',
                'parent_bet_id' => 'required|string',
                'bet_id' => 'required|string',
                'trace_id' => 'required|string'
            ]
        );

        if ($validator->fails()){
            $errorMessage = $validator->errors();
            return ApiResponse::result($errorMessage, ApiState::FIELD_MISSING);
        }

        return $requireInputs;
    }

    /**
     * 小表驗證必填欄位
     * @param array $requireInputs
     * @param array $extraInputs
     * @param array $rawData
     * @return array
     */
    function betlog(array $requireInputs, array $extraInputs, array $rawData): array
    {
        $inputs = $requireInputs;
        $validator = $this->validation->make(
            $inputs,
            [
                'player_name' => 'required|string',
                'member_code' => 'required|string',
                'trans_type' => 'required|string',
                'game_code' => 'required|string',
                'game_type' => 'required|string',
                'bet_amount' => 'required|numeric',
                'win_amount' => 'required|numeric',
                'game_time' => 'required|min:13|max:13',
                'bet_id' => 'required|string',
                'parent_bet_id' => 'required|string',
                'trace_id' => 'required|string',
                'valid_bet' => 'required|numeric',
            ]
        );

        if ($validator->fails()){
            $errorMessage = $validator->errors();
            return ApiResponse::result($errorMessage, ApiState::FIELD_MISSING);
        }

        $record = [
            'player_name' => $inputs['player_name'],
            'member_code' => $inputs['member_code'],
            'trans_type' => $inputs['trans_type'],
            'game_code' => $inputs['game_code'],
            'game_type' => $inputs['game_type'],
            'bet_amount' => floatval($inputs['bet_amount']),
            'win_amount' => floatval($inputs['win_amount']),
            'game_time' => intval($inputs['game_time']),
            'parent_bet_id' => $inputs['parent_bet_id'],
            'bet_id' => $inputs['bet_id'],
            'trace_id' => $inputs['trace_id'],
            'valid_bet' => floatval($inputs['valid_bet'])
        ];

        $result = array_merge($record, $extraInputs);
        $result['raw'] = $rawData;

        return $result;
    }
}
