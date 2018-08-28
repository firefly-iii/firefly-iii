<?php

/**
 * TransactionJournalFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;
use Log;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    use JournalServiceTrait, TransactionTypeTrait;
    /** @var User The user */
    private $user;

    /**
     * Store a new transaction journal.
     *
     * @param array $data
     *
     * @return TransactionJournal
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function create(array $data): TransactionJournal
    {
        Log::debug('Start of TransactionJournalFactory::create()');
        // store basic journal first.
        $type            = $this->findTransactionType($data['type']);
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser($this->user);
        Log::debug(sprintf('Going to store a %s', $type->type));
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $type->id,
                'bill_id'                 => null,
                'transaction_currency_id' => $defaultCurrency->id,
                'description'             => $data['description'],
                'date'                    => $data['date']->format('Y-m-d'),
                'order'                   => 0,
                'tag_count'               => 0,
                'completed'               => 0,
            ]
        );

        if (isset($data['transactions'][0]['amount']) && '' === $data['transactions'][0]['amount']) {
            Log::error('Empty amount in data', $data);
        }

        // store basic transactions:
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user);

        Log::debug(sprintf('Found %d transactions in array.', \count($data['transactions'])));
        /** @var array $trData */
        foreach ($data['transactions'] as $index => $trData) {
            Log::debug(sprintf('Now storing transaction %d of %d', $index + 1, \count($data['transactions'])));
            $factory->createPair($journal, $trData);
        }
        $journal->completed = true;
        $journal->save();

        // link bill:
        $this->connectBill($journal, $data);

        // link piggy bank (if transfer)
        $this->connectPiggyBank($journal, $data);

        // link tags:
        $this->connectTags($journal, $data);

        // store note:
        $this->storeNote($journal, (string)$data['notes']);

        // store date meta fields (if present):
        $fields = ['sepa-cc', 'sepa-ct-op', 'sepa-ct-id', 'sepa-db', 'sepa-country', 'sepa-ep', 'sepa-ci', 'interest_date', 'book_date', 'process_date',
                   'due_date', 'recurrence_id', 'payment_date', 'invoice_date', 'internal_reference', 'bunq_payment_id', 'importHash', 'importHashV2',
                   'external_id', 'sepa-batch-id'];

        foreach ($fields as $field) {
            $this->storeMeta($journal, $data, $field);
        }
        Log::debug('End of TransactionJournalFactory::create()');

        // invalidate cache.
        app('preferences')->mark();

        return $journal;
    }

    /**
     * Set the user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Link a piggy bank to this journal.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectPiggyBank(TransactionJournal $journal, array $data): void
    {
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user);

        $piggyBank = $factory->find($data['piggy_bank_id'], $data['piggy_bank_name']);
        if (null !== $piggyBank) {
            /** @var PiggyBankEventFactory $factory */
            $factory = app(PiggyBankEventFactory::class);
            $factory->create($journal, $piggyBank);
        }
    }

}
