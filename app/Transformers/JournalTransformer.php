<?php
/**
 * JournalTransformer.php
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


use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\TransformerAbstract;

/**
 * Class JournalTransformer
 */
class JournalTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['attachments', 'notes', 'transactions', 'user', 'tags', 'budget', 'category', 'bill', 'meta', 'piggy_bank_events'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['transactions',];

    /**
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includeTransactions(TransactionJournal $journal): FractalCollection
    {
        $tasker = app(JournalTaskerInterface::class);
        $tasker->setUser($journal->user);
        $transactions = $tasker->getTransactionsOverview($journal);

        return $this->collection($transactions, new TransactionTransformer, 'transaction');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function transform(TransactionJournal $journal): array
    {
        return [
            'id'          => (int)$journal->id,
            'type'        => $journal->transactionType->type,
            'description' => $journal->description,
            'date'        => $journal->date->format('Y-m-d'),
            'order'       => $journal->order,
            'completed'   => $journal->completed,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/transaction_journal/' . $journal->id,
                ],
            ],
        ];
    }

}