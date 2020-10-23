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


use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class BudgetTransformer
 */
class BudgetTransformer extends AbstractTransformer
{
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * BudgetTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->opsRepository = app(OperationsRepositoryInterface::class);
        $this->repository    = app(BudgetRepositoryInterface::class);
    }

    /**
     * Transform a budget.
     *
     * @param Budget $budget
     *
     * @return array
     */
    public function transform(Budget $budget): array
    {
        $this->opsRepository->setUser($budget->user);
        $start      = $this->parameters->get('start');
        $end        = $this->parameters->get('end');
        $autoBudget = $this->repository->getAutoBudget($budget);
        $spent      = [];
        if (null !== $start && null !== $end) {
            $spent  = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$budget])));
        }

        $abCurrencyId   = null;
        $abCurrencyCode = null;
        $abType         = null;
        $abAmount       = null;
        $abPeriod       = null;

        $types = [
            AutoBudget::AUTO_BUDGET_RESET    => 'reset',
            AutoBudget::AUTO_BUDGET_ROLLOVER => 'rollover',
        ];

        if (null !== $autoBudget) {
            $abCurrencyId   = (int) $autoBudget->transactionCurrency->id;
            $abCurrencyCode = $autoBudget->transactionCurrency->code;
            $abType         = $types[$autoBudget->auto_budget_type];
            $abAmount       = number_format((float) $autoBudget->amount, $autoBudget->transactionCurrency->decimal_places, '.', '');
            $abPeriod       = $autoBudget->period;
        }

        return [
            'id'                        => (int)$budget->id,
            'created_at'                => $budget->created_at->toAtomString(),
            'updated_at'                => $budget->updated_at->toAtomString(),
            'active'                    => $budget->active,
            'name'                      => $budget->name,
            'auto_budget_type'          => $abType,
            'auto_budget_period'        => $abPeriod,
            'auto_budget_currency_id'   => $abCurrencyId,
            'auto_budget_currency_code' => $abCurrencyCode,
            'auto_budget_amount'        => $abAmount,
            'spent'                     => $spent,
            'links'                     => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/' . $budget->id,
                ],
            ],
        ];
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = number_format((float) $data['sum'], (int) $data['currency_decimal_places'], '.', '');
            $return[]    = $data;
        }

        return $return;
    }

}
