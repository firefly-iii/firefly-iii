<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Rules\Account\IsUniqueAccount;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Rules\UniqueAccountNumber;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AccountRequest extends ResourceRequest
{
    use ConvertsDataTypes;

    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        Log::debug(__METHOD__);
        $accountRoles   = implode(',', config('firefly.accountRoles'));
        $ccPaymentTypes = implode(',', array_keys(config('firefly.ccTypes')));
        $types          = implode(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $type           = $this->convertString('type');
        // var_dump($types);exit;

        return [
            'name'                         => ['required', 'max:1024', 'min:1'], // , new IsUniqueAccount()
            'account_type'                 => ['required', 'max:1024', 'min:1', sprintf('in:%s', $types)],
            //            'iban'                 => ['iban', 'nullable', new UniqueIban(null, $type)],
            //            'bic'                  => 'bic|nullable',
            //            'account_number'       => ['min:1', 'max:255', 'nullable', new UniqueAccountNumber(null, $type)],
            //            'opening_balance'      => 'numeric|required_with:opening_balance_date|nullable',
            //            'opening_balance_date' => 'date|required_with:opening_balance|nullable',
            //            'virtual_balance'      => 'numeric|nullable',
            //            'order'                => 'numeric|nullable',
            //            'currency_id'          => 'numeric|exists:transaction_currencies,id',
            //            'currency_code'        => 'min:3|max:3|exists:transaction_currencies,code',
            //            'active'               => [new IsBoolean()],
            //            'include_net_worth'    => [new IsBoolean()],
            //            'account_role'         => sprintf('nullable|in:%s|required_if:type,asset', $accountRoles),
            //            'credit_card_type'     => sprintf('nullable|in:%s|required_if:account_role,ccAsset', $ccPaymentTypes),
            //            'monthly_payment_date' => 'nullable|date|required_if:account_role,ccAsset|required_if:credit_card_type,monthlyFull',
            //            'liability_type'       => 'nullable|required_if:type,liability|required_if:type,liabilities|in:loan,debt,mortgage',
            //            'liability_amount'     => ['required_with:liability_start_date', new IsValidPositiveAmount()],
            //            'liability_start_date' => 'required_with:liability_amount|date',
            //            'liability_direction'  => 'nullable|required_if:type,liability|required_if:type,liabilities|in:credit,debit',
            //            'interest'             => 'min:0|max:100|numeric',
            //            'interest_period'      => sprintf('nullable|in:%s', implode(',', config('firefly.interest_periods'))),
            //            'notes'                => 'min:0|max:32768',
        ];
    }
}
