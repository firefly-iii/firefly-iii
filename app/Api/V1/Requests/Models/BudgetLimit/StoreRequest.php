<?php

/**
 * BudgetLimitStoreRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\BudgetLimit;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Budget;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        return [
            'start'         => $this->getCarbonDate('start'),
            'end'           => $this->getCarbonDate('end'),
            'amount'        => $this->convertString('amount'),
            'currency_id'   => $this->convertInteger('currency_id'),
            'currency_code' => $this->convertString('currency_code'),
            'notes'         => $this->stringWithNewlines('notes'),

            // for webhooks:
            'fire_webhooks' => $this->boolean('fire_webhooks', true),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'start'         => 'required|before:end|date',
            'end'           => 'required|after:start|date',
            'amount'        => ['required', new IsValidPositiveAmount()],
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:51|exists:transaction_currencies,code',
            'notes'         => 'nullable|min:0|max:32768',

            // webhooks
            'fire_webhooks'              => [new IsBoolean()],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $budget     = $this->route()->parameter('budget');
        $validator->after(
            static function (Validator $validator) use ($budget): void {
                if(0 !== count($validator->failed())) {
                    return;
                }
                $data = $validator->getData();

                // if no currency has been provided, use the user's default currency:
                /** @var TransactionCurrencyFactory $factory */
                $factory                        = app(TransactionCurrencyFactory::class);
                $currency                       = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
                if (null === $currency) {
                    $currency = Amount::getPrimaryCurrency();
                }
                $currency->enabled              = true;
                $currency->save();

                // validator already concluded start and end are valid dates:
                $start = Carbon::parse($data['start'], config('app.timezone'));
                $end   = Carbon::parse($data['end'], config('app.timezone'));

                // find limit with same date range and currency.
                $limit                          = $budget->budgetlimits()
                                                         ->where('budget_limits.start_date', $start->format('Y-m-d'))
                                                         ->where('budget_limits.end_date', $end->format('Y-m-d'))
                                                         ->where('budget_limits.transaction_currency_id', $currency->id)
                                                         ->first(['budget_limits.*'])
                ;
                if(null !== $limit) {
                    $validator->errors()->add('start', trans('validation.limit_exists'));
                }
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
