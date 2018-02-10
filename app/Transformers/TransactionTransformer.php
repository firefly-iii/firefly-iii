<?php
/**
 * TransactionTransformer.php
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


use League\Fractal\TransformerAbstract;

/**
 * Class TransactionTransformer
 */
class TransactionTransformer extends TransformerAbstract
{
    /**
     * @param array $original
     *
     * @return array
     */
    public function transform(array $original): array
    {
        $id            = $original['source_id'];
        $foreignAmount = null;
        if (!is_null($original['foreign_source_amount'])) {
            $foreignAmount = round($original['foreign_source_amount'], $original['foreign_currency_dp']);
        }
        $return = [
            'id'                    => $id,
            'amount'                => round($original['source_amount'], $original['transaction_currency_dp']),
            'currency_id'           => $original['transaction_currency_id'],
            'currency_code'         => $original['transaction_currency_code'],
            'foreign_amount'        => $foreignAmount,
            'foreign_currency_id'   => $original['foreign_currency_id'],
            'foreign_currency_code' => $original['foreign_currency_code'],
            'description'           => $original['description'],
            'links'                 => [
                [
                    'rel' => 'self',
                    'uri' => '/transaction/' . $id,
                ],
            ],
        ];

        // todo source account, dest account, budget, category

        return $return;
    }

}