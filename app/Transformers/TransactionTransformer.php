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


use FireflyIII\Models\Transaction;
use League\Fractal\TransformerAbstract;

/**
 * Class TransactionTransformer
 */
class TransactionTransformer extends TransformerAbstract
{
    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    public function transform(Transaction $transaction): array
    {
        $opposing = Transaction
            ::where('transaction_journal_id', $transaction->transaction_journal_id)
            ->where('identifier', $transaction->identifier)
            ->where('amount', $transaction->amount * -1)
            ->whereNull('deleted_at')
            ->first(['transactions.*']);

        $data = [
            'id'                  => (int)$transaction->id,
            'updated_at'          => $transaction->updated_at->toAtomString(),
            'created_at'          => $transaction->created_at->toAtomString(),
            'source_id'           => (int)$transaction->account_id,
            'destination_id'      => $opposing->account_id,
            'description'         => $transaction->description,
            'currency_id'         => (int)$transaction->transaction_currency_id,
            'amount'              => (float)$transaction->amount,
            'foreign_currency_id' => is_null($transaction->foreign_currency_id) ? null : (int)$transaction->foreign_currency_id,
            'foreign_amount'      => is_null($transaction->foreign_amount) ? null : (float)$transaction->foreign_amount,
            'identifier'          => (int)$transaction->identifier,

            'links' => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $transaction->id,
                ],
            ],
        ];

        return $data;
    }

}