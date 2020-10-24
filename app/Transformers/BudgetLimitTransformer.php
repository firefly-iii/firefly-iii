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

/**
 * Class BudgetLimitTransformer
 */
class BudgetLimitTransformer extends AbstractTransformer
{
    /**
     * Transform the note.
     *
     * @param BudgetLimit $budgetLimit
     *
     * @return array
     */
    public function transform(BudgetLimit $budgetLimit): array
    {
        $currency              = $budgetLimit->transactionCurrency;
        $amount                = $budgetLimit->amount;
        $currencyDecimalPlaces = 2;
        $currencyId            = null;
        $currencyName          = null;
        $currencyCode          = null;
        $currencySymbol        = null;
        if (null !== $currency) {
            $amount                = $budgetLimit->amount;
            $currencyId            = (int) $currency->id;
            $currencyName          = $currency->name;
            $currencyCode          = $currency->code;
            $currencySymbol        = $currency->symbol;
            $currencyDecimalPlaces = $currency->decimal_places;
        }
        $amount = number_format((float) $amount, $currencyDecimalPlaces, '.', '');
        return [
            'id'                      => (int) $budgetLimit->id,
            'created_at'              => $budgetLimit->created_at->toAtomString(),
            'updated_at'              => $budgetLimit->updated_at->toAtomString(),
            'start'                   => $budgetLimit->start_date->format('Y-m-d'),
            'end'                     => $budgetLimit->end_date->format('Y-m-d'),
            'budget_id'               => (int) $budgetLimit->budget_id,
            'currency_id'             => $currencyId,
            'currency_code'           => $currencyCode,
            'currency_name'           => $currencyName,
            'currency_decimal_places' => $currencyName,
            'currency_symbol'         => $currencySymbol,
            'amount'                  => $amount,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/limits/' . $budgetLimit->id,
                ],
            ],
        ];
    }
}
