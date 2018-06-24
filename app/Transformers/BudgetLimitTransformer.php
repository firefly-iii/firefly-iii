<?php
/**
 * BudgetLimitTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;
use FireflyIII\Models\BudgetLimit;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BudgetLimitTransformer
 */
class BudgetLimitTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['budget'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['budget'];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Attach the budget.
     *
     * @codeCoverageIgnore
     *
     * @param BudgetLimit $budgetLimit
     *
     * @return Item
     */
    public function includeBudget(BudgetLimit $budgetLimit): Item
    {
        return $this->item($budgetLimit->budget, new BudgetTransformer($this->parameters), 'budgets');
    }

    /**
     * Transform the note.
     *
     * @param BudgetLimit $budgetLimit
     *
     * @return array
     */
    public function transform(BudgetLimit $budgetLimit): array
    {
        $data = [
            'id'         => (int)$budgetLimit->id,
            'updated_at' => $budgetLimit->updated_at->toAtomString(),
            'created_at' => $budgetLimit->created_at->toAtomString(),
            'start_date' => $budgetLimit->start_date->format('Y-m-d'),
            'end_date'   => $budgetLimit->end_date->format('Y-m-d'),
            'amount'     => $budgetLimit->amount,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/budget_limits/' . $budgetLimit->id,
                ],
            ],
        ];

        return $data;
    }
}