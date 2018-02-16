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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\User;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    /** @var User */
    private $user;

    /**
     * TransactionJournalFactory constructor.
     */
    public function __construct()
    {

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
        $type            = $this->findTransactionType($data['type']);
        $bill            = $this->findBill($data['bill_id'], $data['bill_name']);
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);

        $values = [
            'user_id'                 => $data['user'],
            'transaction_type_id'     => $type->id,
            'bill_id'                 => is_null($bill) ? null : $bill->id,
            'transaction_currency_id' => $defaultCurrency->id,
            'description'             => $data['description'],
            'date'                    => $data['date']->format('Y-m-d'),
            'order'                   => 0,
            'tag_count'               => 0,
            'completed'               => 0,
        ];

        $journal = $repository->storeBasic($values);

        // todo link other stuff to journal (meta-data etc). tags

        // start creating transactions:
        /** @var array $trData */
        foreach ($data['transactions'] as $trData) {
            $factory = new TransactionFactory();
            $factory->setUser($this->user);

            $trData['reconciled'] = $data['reconciled'] ?? false;
            $factory->createPair($journal, $trData);
        }
        $repository->markCompleted($journal);

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
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($this->user);

        // first find by ID:
        if ($billId > 0) {
            /** @var Bill $bill */
            $bill = $repository->find($billId);
            if (!is_null($bill)) {
                return $bill;
            }
        }

        // then find by name:
        if (strlen($billName) > 0) {
            $bill = $repository->findByName($billName);
            if (!is_null($bill)) {
                return $bill;
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
    private function findTransactionType(string $type): TransactionType
    {
        /** @var TransactionTypeRepositoryInterface $repository */
        $repository      = app(TransactionTypeRepositoryInterface::class);
        $transactionType = $repository->findByType($type);
        if (is_null($transactionType)) {
            throw new FireflyException(sprintf('Could not find transaction type for "%s"', $type));
        }

        return $transactionType;
    }

}