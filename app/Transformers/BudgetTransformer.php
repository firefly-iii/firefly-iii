<?php

/**
 * BudgetTransformer.php
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

use FireflyIII\Enums\AutoBudgetType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BudgetTransformer
 */
class BudgetTransformer extends AbstractTransformer
{
    private readonly bool                          $convertToPrimary;
    private readonly TransactionCurrency           $primary;
    private readonly OperationsRepositoryInterface $opsRepository;
    private readonly BudgetRepositoryInterface     $repository;

    /**
     * BudgetTransformer constructor.
     */
    public function __construct()
    {
        $this->opsRepository    = app(OperationsRepositoryInterface::class);
        $this->repository       = app(BudgetRepositoryInterface::class);
        $this->parameters       = new ParameterBag();
        $this->primary          = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
    }

    /**
     * Transform a budget.
     */
    public function transform(Budget $budget): array
    {
        $this->opsRepository->setUser($budget->user);
        $start      = $this->parameters->get('start');
        $end        = $this->parameters->get('end');
        $autoBudget = $this->repository->getAutoBudget($budget);
        $spent      = [];
        if (null !== $start && null !== $end) {
            $spent = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget])));
        }

        // info for auto budget.
        $abType     = null;
        $abAmount   = null;
        $abPrimary  = null;
        $abPeriod   = null;
        $notes      = $this->repository->getNoteText($budget);

        $types      = [
            AutoBudgetType::AUTO_BUDGET_RESET->value    => 'reset',
            AutoBudgetType::AUTO_BUDGET_ROLLOVER->value => 'rollover',
            AutoBudgetType::AUTO_BUDGET_ADJUSTED->value => 'adjusted',
        ];
        $currency   = $autoBudget?->transactionCurrency;
        $primary    = $this->primary;
        if (!$this->convertToPrimary) {
            $primary = null;
        }
        if (null === $autoBudget) {
            $currency = $primary;
        }
        if (null !== $autoBudget) {
            $abType    = $types[$autoBudget->auto_budget_type];
            $abAmount  = Steam::bcround($autoBudget->amount, $currency->decimal_places);
            $abPrimary = $this->convertToPrimary ? Steam::bcround($autoBudget->native_amount, $primary->decimal_places) : null;
            $abPeriod  = $autoBudget->period;
        }

        return [
            'id'                              => (string) $budget->id,
            'created_at'                      => $budget->created_at->toAtomString(),
            'updated_at'                      => $budget->updated_at->toAtomString(),
            'active'                          => $budget->active,
            'name'                            => $budget->name,
            'order'                           => $budget->order,
            'notes'                           => $notes,
            'auto_budget_type'                => $abType,
            'auto_budget_period'              => $abPeriod,

            'currency_id'                     => null === $autoBudget ? null : (string) $autoBudget->transactionCurrency->id,
            'currency_code'                   => $autoBudget?->transactionCurrency->code,
            'currency_name'                   => $autoBudget?->transactionCurrency->name,
            'currency_decimal_places'         => $autoBudget?->transactionCurrency->decimal_places,
            'currency_symbol'                 => $autoBudget?->transactionCurrency->symbol,

            'primary_currency_id'             => $primary instanceof TransactionCurrency ? (string) $primary->id : null,
            'primary_currency_code'           => $primary?->code,
            'primary_currency_symbol'         => $primary?->symbol,
            'primary_currency_decimal_places' => $primary?->decimal_places,

            // amount and primary currency amount if present.

            'auto_budget_amount'              => $abAmount,
            'pc_auto_budget_amount'           => $abPrimary,
            'spent'                           => $spent, // always in primary currency.
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/'.$budget->id,
                ],
            ],
        ];
    }

    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = Steam::bcround($data['sum'], (int) $data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
