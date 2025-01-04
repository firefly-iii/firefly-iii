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
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BudgetTransformer
 */
class BudgetTransformer extends AbstractTransformer
{
    private OperationsRepositoryInterface $opsRepository;
    private BudgetRepositoryInterface     $repository;

    /**
     * BudgetTransformer constructor.
     */
    public function __construct()
    {
        $this->opsRepository = app(OperationsRepositoryInterface::class);
        $this->repository    = app(BudgetRepositoryInterface::class);
        $this->parameters    = new ParameterBag();
    }

    /**
     * Transform a budget.
     */
    public function transform(Budget $budget): array
    {
        $this->opsRepository->setUser($budget->user);
        $start          = $this->parameters->get('start');
        $end            = $this->parameters->get('end');
        $autoBudget     = $this->repository->getAutoBudget($budget);
        $spent          = [];
        if (null !== $start && null !== $end) {
            $spent = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget])));
        }

        $abCurrencyId   = null;
        $abCurrencyCode = null;
        $abType         = null;
        $abAmount       = null;
        $abPeriod       = null;
        $notes          = $this->repository->getNoteText($budget);

        $types          = [
            AutoBudgetType::AUTO_BUDGET_RESET->value    => 'reset',
            AutoBudgetType::AUTO_BUDGET_ROLLOVER->value => 'rollover',
            AutoBudgetType::AUTO_BUDGET_ADJUSTED->value => 'adjusted',
        ];

        if (null !== $autoBudget) {
            $abCurrencyId   = (string) $autoBudget->transactionCurrency->id;
            $abCurrencyCode = $autoBudget->transactionCurrency->code;
            $abType         = $types[$autoBudget->auto_budget_type];
            $abAmount       = app('steam')->bcround($autoBudget->amount, $autoBudget->transactionCurrency->decimal_places);
            $abPeriod       = $autoBudget->period;
        }

        return [
            'id'                        => (string) $budget->id,
            'created_at'                => $budget->created_at->toAtomString(),
            'updated_at'                => $budget->updated_at->toAtomString(),
            'active'                    => $budget->active,
            'name'                      => $budget->name,
            'order'                     => $budget->order,
            'notes'                     => $notes,
            'auto_budget_type'          => $abType,
            'auto_budget_period'        => $abPeriod,
            'auto_budget_currency_id'   => $abCurrencyId,
            'auto_budget_currency_code' => $abCurrencyCode,
            'auto_budget_amount'        => $abAmount,
            'spent'                     => $spent,
            'links'                     => [
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
            $data['sum'] = app('steam')->bcround($data['sum'], (int) $data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
