<?php

/**
 * BudgetLimitTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use League\Fractal\Resource\Item;

/**
 * Class BudgetLimitTransformer
 */
class BudgetLimitTransformer extends AbstractTransformer
{
    protected array               $availableIncludes
        = [
            'budget',
        ];
    protected bool                $convertToPrimary;
    protected TransactionCurrency $primaryCurrency;

    public function __construct()
    {
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
    }

    /**
     * Include Budget
     *
     * @return Item
     */
    public function includeBudget(BudgetLimit $limit)
    {
        return $this->item($limit->budget, new BudgetTransformer(), 'budgets');
    }

    /**
     * Transform the note.
     */
    public function transform(BudgetLimit $budgetLimit): array
    {

        $currency = $budgetLimit->meta['currency'];
        $amount   = Steam::bcround($budgetLimit->amount, $currency->decimal_places);
        $pcAmount = null;
        if ($this->convertToPrimary && $currency->id === $this->primaryCurrency->id) {
            $pcAmount = $amount;
        }
        if ($this->convertToPrimary && $currency->id !== $this->primaryCurrency->id) {
            $pcAmount = Steam::bcround($budgetLimit->native_amount, $this->primaryCurrency->decimal_places);
        }

        return [
            'id'                              => (string)$budgetLimit->id,
            'created_at'                      => $budgetLimit->created_at->toAtomString(),
            'updated_at'                      => $budgetLimit->updated_at->toAtomString(),
            'start'                           => $budgetLimit->start_date->toAtomString(),
            'end'                             => $budgetLimit->end_date->endOfDay()->toAtomString(),
            'budget_id'                       => (string)$budgetLimit->budget_id,

            // currency settings according to 6.3.0
            'object_has_currency_setting'     => true,

            'currency_id'                     => (string)$currency->id,
            'currency_name'                   => $currency->name,
            'currency_code'                   => $currency->code,
            'currency_symbol'                 => $currency->symbol,
            'currency_decimal_places'         => $currency->decimal_places,

            'primary_currency_id'             => (int)$this->primaryCurrency->id,
            'primary_currency_name'           => $this->primaryCurrency->name,
            'primary_currency_code'           => $this->primaryCurrency->code,
            'primary_currency_symbol'         => $this->primaryCurrency->symbol,
            'primary_currency_decimal_places' => $this->primaryCurrency->decimal_places,

            'amount'                          => $amount,
            'pc_amount'                       => $pcAmount,
            'period'                          => $budgetLimit->period,
            'spent'                           => $budgetLimit->meta['spent'],
            'pc_spent'                        => $budgetLimit->meta['pc_spent'],
            'notes'                           => $budgetLimit->meta['notes'],
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/limits/'.$budgetLimit->id,
                ],
            ],
        ];
    }
}
