<?php

/**
 * AvailableBudgetTransformer.php
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

use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;

/**
 * Class AvailableBudgetTransformer
 */
class AvailableBudgetTransformer extends AbstractTransformer
{
    private readonly bool                          $convertToPrimary;
    private readonly TransactionCurrency           $primary;
    private readonly NoBudgetRepositoryInterface   $noBudgetRepository;
    private readonly OperationsRepositoryInterface $opsRepository;
    private readonly BudgetRepositoryInterface     $repository;

    /**
     * CurrencyTransformer constructor.
     */
    public function __construct()
    {
        $this->repository         = app(BudgetRepositoryInterface::class);
        $this->opsRepository      = app(OperationsRepositoryInterface::class);
        $this->noBudgetRepository = app(NoBudgetRepositoryInterface::class);
        $this->primary            = Amount::getPrimaryCurrency();
        $this->convertToPrimary   = Amount::convertToPrimary();
    }

    /**
     * Transform the note.
     */
    public function transform(AvailableBudget $availableBudget): array
    {
        $this->repository->setUser($availableBudget->user);
        $currency = $availableBudget->transactionCurrency;
        $amount   = app('steam')->bcround($availableBudget->amount, $currency->decimal_places);
        $pcAmount = null;

        if ($this->convertToPrimary) {
            $pcAmount = app('steam')->bcround($availableBudget->native_amount, $this->primary->decimal_places);
        }

        $data  = [
            'id'                      => (string) $availableBudget->id,
            'created_at'              => $availableBudget->created_at->toAtomString(),
            'updated_at'              => $availableBudget->updated_at->toAtomString(),

            // currencies according to 6.3.0
            'currency_id'             => (string) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,

            'primary_currency_id'             => (string) $this->primary->id,
            'primary_currency_code'           => $this->primary->code,
            'primary_currency_symbol'         => $this->primary->symbol,
            'primary_currency_decimal_places' => $this->primary->decimal_places,


            'amount'                   => $amount,
            'pc_amount'                => $pcAmount,
            'start'                    => $availableBudget->start_date->toAtomString(),
            'end'                      => $availableBudget->end_date->endOfDay()->toAtomString(),
            'spent_in_budgets'         => $availableBudget->meta['spent_in_budgets'],
            'pc_spent_in_budgets'      => $availableBudget->meta['pc_spent_in_budgets'],
            'spent_outside_budgets'    => $availableBudget->meta['spent_outside_budgets'],
            'pc_spent_outside_budgets' => $availableBudget->meta['pc_spent_outside_budgets'],
            'links'                    => [
                [
                    'rel' => 'self',
                    'uri' => '/available_budgets/' . $availableBudget->id,
                ],
            ],
        ];
        $start = $this->parameters->get('start');
        $end   = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $data['old_spent_in_budgets'] = $this->getSpentInBudgets();
            $data['old_spent_no_budget']  = $this->spentOutsideBudgets();
        }

        return $data;
    }

    private function getSpentInBudgets(): array
    {
        $allActive = $this->repository->getActiveBudgets();
        $sums      = $this->opsRepository->sumExpenses($this->parameters->get('start'), $this->parameters->get('end'), null, $allActive);

        return array_values($sums);
    }

    private function spentOutsideBudgets(): array
    {
        $sums = $this->noBudgetRepository->sumExpenses($this->parameters->get('start'), $this->parameters->get('end'));

        return array_values($sums);
    }
}
