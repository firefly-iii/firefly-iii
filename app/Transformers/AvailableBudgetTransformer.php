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

/**
 * Class AvailableBudgetTransformer
 */
class AvailableBudgetTransformer extends TransformerAbstract
{
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
