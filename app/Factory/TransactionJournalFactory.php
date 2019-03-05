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
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    private $fields;
    /** @var User The user */
    private $user;

    use JournalServiceTrait, TransactionTypeTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->fields = ['sepa-cc', 'sepa-ct-op', 'sepa-ct-id', 'sepa-db', 'sepa-country', 'sepa-ep', 'sepa-ci', 'interest_date', 'book_date', 'process_date',
                         'due_date', 'recurrence_id', 'payment_date', 'invoice_date', 'internal_reference', 'bunq_payment_id', 'importHash', 'importHashV2',
                         'external_id', 'sepa-batch-id', 'original-source'];
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * Store a new transaction journal.
     *
     * @param array $data
     *
     * @return Collection
     * @throws FireflyException
     */
    public function create(array $data): Collection
    {
        Log::debug('Start of TransactionJournalFactory::create()');

        $factory     = app(TransactionFactory::class);
        $journals    = new Collection;
        $carbon      = $data['date'];
        $type        = $this->findTransactionType($data['type']);
        $description = app('steam')->cleanString($data['description']);

        $carbon->setTimezone(config('app.timezone'));
        $factory->setUser($this->user);

        Log::debug(sprintf('New journal(group): %s with description "%s"', $type->type, $description));

        // loop each transaction.
        /**
         * @var int   $index
         * @var array $transactionData
         */
        foreach ($data['transactions'] as $index => $transactionData) {
            Log::debug(sprintf('Now at journal #%d from %d', $index + 1, count($data['transactions'])));

            // catch to stop empty amounts:
            if ('' === (string)$transactionData['amount'] || 0.0 === (float)$transactionData['amount']) {
                continue;
            }
            // currency & foreign currency
            $transactionData['currency']         = $this->getCurrency($data, $index);
            $transactionData['foreign_currency'] = $this->getForeignCurrency($data, $index);

            // store basic journal first.
            $journal = TransactionJournal::create(
                [
                    'user_id'                 => $data['user'],
                    'transaction_type_id'     => $type->id,
                    'bill_id'                 => null,
                    'transaction_currency_id' => $transactionData['currency']->id,
                    'description'             => $description,
                    'date'                    => $carbon->format('Y-m-d H:i:s'),
                    'order'                   => 0,
                    'tag_count'               => 0,
                    'completed'               => 0,
                ]
            );
            Log::debug(sprintf('Stored journal under ID #%d', $journal->id));

            // store transactions for this journal:
            $factory->createPair($journal, $transactionData);

            // save journal:
            $journal->completed = true;
            $journal->save();

            //            // link bill TODO
            //            $this->connectBill($journal, $data);
            //
            //            // link piggy bank (if transfer) TODO
            //            $this->connectPiggyBank($journal, $data);
            //
            //            // link tags: TODO
            //            $this->connectTags($journal, $transactionData);
            //
            //            // store note: TODO
            //            $this->storeNote($journal, $transactionData['notes']);
            //
            //            if ($journal->transactionType->type !== TransactionType::WITHDRAWAL) {
            //                $transactionData['budget_id']   = null;
            //                $transactionData['budget_name'] = null;
            //            }
            //            // save budget  TODO
            //            $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
            //            $this->setBudget($journal, $budget);
            //
            //            // set category TODO
            //            $category = $this->findCategory($data['category_id'], $data['category_name']);
            //            $this->setCategory($journal, $category);
            //
            //            // store meta data TODO
            //            foreach ($this->fields as $field) {
            //                $this->storeMeta($journal, $data, $field);
            //            }

            // add to array
            $journals->push($journal);
        }

        // create group if necessary
        if ($journals->count() > 1) {
            $group = new TransactionGroup;
            $group->user()->associate($this->user);
            $group->title = $description;
            $group->save();
            $group->transactionJournals()->saveMany($journals);

            Log::debug(sprintf('More than one journal, created group #%d.', $group->id));
        }


        Log::debug('End of TransactionJournalFactory::create()');
        // invalidate cache.
        app('preferences')->mark();

        return $journals;
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

    /**
     * @param array $data
     * @param int   $index
     *
     * @return TransactionCurrency
     */
    private function getCurrency(array $data, int $index): TransactionCurrency
    {
        // first check the transaction row itself.
        $row      = $data['transactions'][$index];
        $currency = null;

        // check currency object:
        if (null === $currency && isset($row['currency']) && $row['currency'] instanceof TransactionCurrency) {
            $currency = $row['currency'];
        }

        // check currency ID:
        if (null === $currency && isset($row['currency_id']) && (int)$row['currency_id'] > 0) {
            $currencyId = (int)$row['currency_id'];
            $currency   = TransactionCurrency::find($currencyId);
        }

        // check currency code
        if (null === $currency && isset($row['currency_code']) && 3 === \strlen($row['currency_code'])) {
            $currency = TransactionCurrency::whereCode($row['currency_code'])->first();
        }

        // continue with journal itself.

        // check currency object:
        if (null === $currency && isset($data['currency']) && $data['currency'] instanceof TransactionCurrency) {
            $currency = $data['currency'];
        }

        // check currency ID:
        if (null === $currency && isset($data['currency_id']) && (int)$data['currency_id'] > 0) {
            $currencyId = (int)$data['currency_id'];
            $currency   = TransactionCurrency::find($currencyId);
        }

        // check currency code
        if (null === $currency && isset($data['currency_code']) && 3 === \strlen($data['currency_code'])) {
            $currency = TransactionCurrency::whereCode($data['currency_code'])->first();
        }
        if (null === $currency) {
            // return user's default currency:
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }

        // enable currency:
        if (false === $currency->enabled) {
            $currency->enabled = true;
            $currency->save();
        }
        Log::debug(sprintf('Journal currency will be #%d (%s)', $currency->id, $currency->code));

        return $currency;

    }

    /**
     * @param array $data
     * @param int   $index
     *
     * @return TransactionCurrency|null
     */
    private function getForeignCurrency(array $data, int $index): ?TransactionCurrency
    {
        // first check the transaction row itself.
        $row      = $data['transactions'][$index];
        $currency = null;

        // check currency object:
        if (null === $currency && isset($row['foreign_currency']) && $row['foreign_currency'] instanceof TransactionCurrency) {
            $currency = $row['foreign_currency'];
        }

        // check currency ID:
        if (null === $currency && isset($row['foreign_currency_id']) && (int)$row['foreign_currency_id'] > 0) {
            $currencyId = (int)$row['foreign_currency_id'];
            $currency   = TransactionCurrency::find($currencyId);
        }

        // check currency code
        if (null === $currency && isset($row['foreign_currency_code']) && 3 === \strlen($row['foreign_currency_code'])) {
            $currency = TransactionCurrency::whereCode($row['foreign_currency_code'])->first();
        }

        // continue with journal itself.

        // check currency object:
        if (null === $currency && isset($data['foreign_currency']) && $data['foreign_currency'] instanceof TransactionCurrency) {
            $currency = $data['foreign_currency'];
        }

        // check currency ID:
        if (null === $currency && isset($data['foreign_currency_id']) && (int)$data['foreign_currency_id'] > 0) {
            $currencyId = (int)$data['foreign_currency_id'];
            $currency   = TransactionCurrency::find($currencyId);
        }

        // check currency code
        if (null === $currency && isset($data['foreign_currency_code']) && 3 === \strlen($data['foreign_currency_code'])) {
            $currency = TransactionCurrency::whereCode($data['foreign_currency_code'])->first();
        }

        // enable currency:
        if (null !== $currency && false === $currency->enabled) {
            $currency->enabled = true;
            $currency->save();
        }
        if (null !== $currency) {
            Log::debug(sprintf('Journal foreign currency will be #%d (%s)', $currency->id, $currency->code));
        }
        if (null === $currency) {
            Log::debug('Journal foreign currency will be NULL');
        }

        return $currency;
    }

}
