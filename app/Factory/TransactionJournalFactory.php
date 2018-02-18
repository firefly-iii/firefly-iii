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
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\User;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepository;
    /** @var JournalRepositoryInterface */
    private $repository;
    /** @var TransactionTypeRepositoryInterface */
    private $ttRepository;
    /** @var User */
    private $user;

    /**
     * TransactionJournalFactory constructor.
     */
    public function __construct()
    {
        $this->repository      = app(JournalRepositoryInterface::class);
        $this->billRepository  = app(BillRepositoryInterface::class);
        $this->piggyRepository = app(PiggyBankRepositoryInterface::class);
        $this->ttRepository    = app(TransactionTypeRepositoryInterface::class);

    }

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
        $values          = [
            'user_id'                 => $data['user'],
            'transaction_type_id'     => $type->id,
            'bill_id'                 => null,
            'transaction_currency_id' => $defaultCurrency->id,
            'description'             => $data['description'],
            'date'                    => $data['date']->format('Y-m-d'),
            'order'                   => 0,
            'tag_count'               => 0,
            'completed'               => 0,
        ];

        $journal = $this->repository->storeBasic($values);

        // store basic transactions:
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user);

        /** @var array $trData */
        foreach ($data['transactions'] as $trData) {
            $factory->createPair($journal, $trData);
        }
        $this->repository->markCompleted($journal);

        // link bill:
        $this->connectBill($journal, $data);

        // link piggy bank:
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
        $this->repository->setUser($user);
        $this->billRepository->setUser($user);
        $this->piggyRepository->setUser($user);
    }

    /**
     * Connect bill if present.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectBill(TransactionJournal $journal, array $data): void
    {
        $bill = $this->findBill($data['bill_id'], $data['bill_name']);
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
        $piggyBank = $this->findPiggyBank($data['piggy_bank_id'], $data['piggy_bank_name']);
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
     * Find the given bill based on the ID or the name. ID takes precedence over the name.
     *
     * @param int    $billId
     * @param string $billName
     *
     * @return Bill|null
     */
    protected function findBill(int $billId, string $billName): ?Bill
    {
        if (strlen($billName) === 0 && $billId === 0) {
            return null;
        }
        // first find by ID:
        if ($billId > 0) {
            /** @var Bill $bill */
            $bill = $this->billRepository->find($billId);
            if (!is_null($bill)) {
                return $bill;
            }
        }

        // then find by name:
        if (strlen($billName) > 0) {
            $bill = $this->billRepository->findByName($billName);
            if (!is_null($bill)) {
                return $bill;
            }
        }

        return null;
    }

    /**
     * Find the given bill based on the ID or the name. ID takes precedence over the name.
     *
     * @param int    $piggyBankId
     * @param string $piggyBankName
     *
     * @return PiggyBank|null
     */
    protected function findPiggyBank(int $piggyBankId, string $piggyBankName): ?PiggyBank
    {
        if (strlen($piggyBankName) === 0 && $piggyBankId === 0) {
            return null;
        }
        // first find by ID:
        if ($piggyBankId > 0) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->piggyRepository->find($piggyBankId);
            if (!is_null($piggyBank)) {
                return $piggyBank;
            }
        }


        // then find by name:
        if (strlen($piggyBankName) > 0) {
            /** @var PiggyBank $piggyBank */
            $piggyBank = $this->piggyRepository->findByName($piggyBankName);
            if (!is_null($piggyBank)) {
                return $piggyBank;
            }
        }

        return null;
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
        $transactionType = $this->ttRepository->findByType($type);
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