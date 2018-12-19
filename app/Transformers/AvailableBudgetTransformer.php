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
use Log;

/**
 * Class AvailableBudgetTransformer
 */
class AvailableBudgetTransformer extends AbstractTransformer
{
    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
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
        $currency = $availableBudget->transactionCurrency;
        $data     = [
            'id'                      => (int)$availableBudget->id,
            'created_at'              => $availableBudget->created_at->toAtomString(),
            'updated_at'              => $availableBudget->updated_at->toAtomString(),
            'currency_id'             => $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'amount'                  => round($availableBudget->amount, $currency->decimal_places),
            'start'                   => $availableBudget->start_date->format('Y-m-d'),
            'end'                     => $availableBudget->end_date->format('Y-m-d'),

            'links' => [
                [
                    'rel' => 'self',
                    'uri' => '/available_budgets/' . $availableBudget->id,
                ],
            ],
        ];

        return $data;
    }

}
