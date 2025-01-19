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
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepository;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Item;

/**
 * Class BudgetLimitTransformer
 */
class BudgetLimitTransformer extends AbstractTransformer
{
    protected array $availableIncludes
        = [
            'budget',
        ];

    protected TransactionCurrency $default;
    protected bool                $convertToNative;

    public function __construct()
    {
        $this->default         = Amount::getDefaultCurrency();
        $this->convertToNative = Amount::convertToNative();
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
        $repository            = app(OperationsRepository::class);
        $limitRepos            = app(BudgetLimitRepositoryInterface::class);
        $repository->setUser($budgetLimit->budget->user);
        $limitRepos->setUser($budgetLimit->budget->user);
        $expenses              = $repository->sumExpenses(
            $budgetLimit->start_date,
            $budgetLimit->end_date,
            null,
            new Collection([$budgetLimit->budget]),
            $budgetLimit->transactionCurrency
        );
        $currency              = $budgetLimit->transactionCurrency;
        $amount                = $budgetLimit->amount;
        $notes                 = $limitRepos->getNoteText($budgetLimit);
        $currencyDecimalPlaces = 2;
        $currencyId            = null;
        $currencyName          = null;
        $currencyCode          = null;
        $currencySymbol        = null;
        if (null !== $currency) {
            $amount                = $budgetLimit->amount;
            $currencyId            = $currency->id;
            $currencyName          = $currency->name;
            $currencyCode          = $currency->code;
            $currencySymbol        = $currency->symbol;
            $currencyDecimalPlaces = $currency->decimal_places;
        }
        $amount                = app('steam')->bcround($amount, $currencyDecimalPlaces);
        $default               = $this->default;
        if (!$this->convertToNative) {
            $default = null;
        }


        return [
            'id'                             => (string) $budgetLimit->id,
            'created_at'                     => $budgetLimit->created_at->toAtomString(),
            'updated_at'                     => $budgetLimit->updated_at->toAtomString(),
            'start'                          => $budgetLimit->start_date->toAtomString(),
            'end'                            => $budgetLimit->end_date->endOfDay()->toAtomString(),
            'budget_id'                      => (string) $budgetLimit->budget_id,
            'currency_id'                    => (string) $currencyId,
            'currency_code'                  => $currencyCode,
            'currency_name'                  => $currencyName,
            'currency_decimal_places'        => $currencyDecimalPlaces,
            'currency_symbol'                => $currencySymbol,
            'native_currency_id'             => null === $default ? null : (string) $default->id,
            'native_currency_code'           => $default?->code,
            'native_currency_symbol'         => $default?->symbol,
            'native_currency_decimal_places' => $default?->decimal_places,
            'amount'                         => $amount,
            'native_amount'                  => $this->convertToNative ? app('steam')->bcround($budgetLimit->native_amount, $default->decimal_places) : null,
            'period'                         => $budgetLimit->period,
            'spent'                          => $expenses[$currencyId]['sum'] ?? '0', // will be in native if convertToNative.
            'notes'                          => '' === $notes ? null : $notes,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/limits/'.$budgetLimit->id,
                ],
            ],
        ];
    }
}
