<?php
/**
 * AvailableBudgetTransformer.php
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


use FireflyIII\Models\AvailableBudget;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

class AvailableBudgetTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['transaction_currency', 'user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['transaction_currency'];

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
     * Attach the currency.
     *
     * @codeCoverageIgnore
     *
     * @param AvailableBudget $availableBudget
     *
     * @return Item
     */
    public function includeTransactionCurrency(AvailableBudget $availableBudget): Item
    {
        return $this->item($availableBudget->transactionCurrency, new CurrencyTransformer($this->parameters), 'transaction_currencies');
    }

    /**
     * Attach the user.
     *
     * @codeCoverageIgnore
     *
     * @param AvailableBudget $availableBudget
     *
     * @return Item
     */
    public function includeUser(AvailableBudget $availableBudget): Item
    {
        return $this->item($availableBudget->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * Transform the note.
     *
     * @param AvailableBudget $availableBudget
     *
     * @return array
     */
    public function transform(AvailableBudget $availableBudget): array
    {
        $data = [
            'id'         => (int)$availableBudget->id,
            'updated_at' => $availableBudget->updated_at->toAtomString(),
            'created_at' => $availableBudget->created_at->toAtomString(),
            'start_date' => $availableBudget->start_date->format('Y-m-d'),
            'end_date'   => $availableBudget->end_date->format('Y-m-d'),
            'amount'     => round($availableBudget->amount, $availableBudget->transactionCurrency->decimal_places),
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/available_budgets/' . $availableBudget->id,
                ],
            ],
        ];

        return $data;
    }

}