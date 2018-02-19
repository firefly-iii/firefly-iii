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
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    /** @var User */
    private $user;

    /**
     * Create a new transaction journal and associated transactions.
     *
     * @param array $data
     *
     * @return TransactionJournal
     * @throws FireflyException
     */
    public function create(array $data): TransactionJournal
    {
        // store basic journal first.
        $type            = $this->findTransactionType($data['type']);
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser($this->user);
        $journal         = TransactionJournal::create(
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

        // store basic transactions:
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user);

        /** @var array $trData */
        foreach ($data['transactions'] as $trData) {
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
        $this->storeNote($journal, $data['notes']);

        // store date meta fields (if present):
        $this->storeMeta($journal, $data, 'interest_date');
        $this->storeMeta($journal, $data, 'book_date');
        $this->storeMeta($journal, $data, 'process_date');
        $this->storeMeta($journal, $data, 'due_date');
        $this->storeMeta($journal, $data, 'payment_date');
        $this->storeMeta($journal, $data, 'invoice_date');
        $this->storeMeta($journal, $data, 'internal_reference');

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
     * Connect bill if present.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectBill(TransactionJournal $journal, array $data): void
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user);
        $bill = $factory->find($data['bill_id'], $data['bill_name']);

        if (!is_null($bill)) {
            $journal->bill_id = $bill->id;
            $journal->save();
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectPiggyBank(TransactionJournal $journal, array $data): void
    {
        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($this->user);

        $piggyBank = $factory->find($data['piggy_bank_id'], $data['piggy_bank_name']);
        if (!is_null($piggyBank)) {
            /** @var PiggyBankEventFactory $factory */
            $factory = app(PiggyBankEventFactory::class);
            $factory->create($journal, $piggyBank);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectTags(TransactionJournal $journal, array $data): void
    {
        $factory = app(TagFactory::class);
        $factory->setUser($journal->user);
        foreach ($data['tags'] as $string) {
            $tag = $factory->findOrCreate($string);
            $journal->tags()->save($tag);
        }
    }

    /**
     * Get the transaction type. Since this is mandatory, will throw an exception when nothing comes up. Will always
     * use TransactionType repository.
     *
     * @param string $type
     *
     * @return TransactionType
     * @throws FireflyException
     */
    protected function findTransactionType(string $type): TransactionType
    {
        $factory         = app(TransactionTypeFactory::class);
        $transactionType = $factory->find($type);
        if (is_null($transactionType)) {
            throw new FireflyException(sprintf('Could not find transaction type for "%s"', $type));
        }

        return $transactionType;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     * @param string             $field
     */
    protected function storeMeta(TransactionJournal $journal, array $data, string $field): void
    {
        $value = $data[$field];
        if (!is_null($value)) {
            $set = [
                'journal' => $journal,
                'name'    => $field,
                'data'    => $data[$field],
            ];
            /** @var TransactionJournalMetaFactory $factory */
            $factory = app(TransactionJournalMetaFactory::class);
            $factory->updateOrCreate($set);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $notes
     */
    protected function storeNote(TransactionJournal $journal, string $notes): void
    {
        if (strlen($notes) > 0) {
            $note = new Note;
            $note->noteable()->associate($journal);
            $note->text = $notes;
            $note->save();
        }

    }

}