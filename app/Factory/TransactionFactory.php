<?php
/**
 * TransactionFactory.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        $foreignCurrencyId = is_null($data['foreign_currency']) ? null : $data['foreign_currency']->id;
        $values            = [
            'reconciled'              => $data['reconciled'],
            'account_id'              => $data['account']->id,
            'transaction_journal_id'  => $data['transaction_journal']->id,
            'description'             => $data['description'],
            'transaction_currency_id' => $data['currency']->id,
            'amount'                  => $data['amount'],
            'foreign_amount'          => $data['foreign_amount'],
            'foreign_currency_id'     => $foreignCurrencyId,
            'identifier'              => $data['identifier'],
        ];
        /** @var JournalRepositoryInterface $repository */
        $repository  = app(JournalRepositoryInterface::class);
        $transaction = $repository->storeBasicTransaction($values);

        // todo: add budget, category


        return $transaction;
    }

    /**
     * Create a pair of transactions based on the data given in the array.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return Collection
     * @throws FireflyException
     */
    public function createPair(TransactionJournal $journal, array $data): Collection
    {
        // all this data is the same for both transactions:
        $currency        = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $foreignCurrency = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        $budget          = $this->findBudget($data['budget_id'], $data['budget_name']);
        $category        = $this->findCategory($data['category_id'], $data['category_name']);


        // type of source account depends on journal type:
        $sourceType    = $this->accountType($journal, 'source');
        $sourceAccount = $this->findAccount($sourceType, $data['source_account_id'], $data['source_account_name']);

        // same for destination account:
        $destinationType    = $this->accountType($journal, 'destination');
        $destinationAccount = $this->findAccount($destinationType, $data['destination_account_id'], $data['destination_account_name']);

        // first make a "negative" (source) transaction based on the data in the array.
        $sourceTransactionData = [
            'description'         => $journal->description === $data['description'] ? null : $data['description'],
            'amount'              => app('steam')->negative(strval($data['amount'])),
            'foreign_amount'      => is_null($data['foreign_amount']) ? null : app('steam')->negative(strval($data['foreign_amount'])),
            'currency'            => $currency,
            'foreign_currency'    => $foreignCurrency,
            'budget'              => $budget,
            'category'            => $category,
            'account'             => $sourceAccount,
            'transaction_journal' => $journal,
            'reconciled'          => $data['reconciled'],
            'identifier'          => $data['identifier'],
        ];
        $this->create($sourceTransactionData);


        print_r($data);
        print_r($sourceTransactionData);
        exit;


        // then make a "positive" transaction based on the data in the array.

    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $direction
     *
     * @return string
     * @throws FireflyException
     */
    protected function accountType(TransactionJournal $journal, string $direction): string
    {
        $types = [];
        $type  = $journal->transactionType->type;
        switch ($type) {
            default:
                throw new FireflyException(sprintf('Cannot handle type "%s" in accountType()', $type));
            case TransactionType::WITHDRAWAL:
                $types['source']      = AccountType::ASSET;
                $types['destination'] = AccountType::EXPENSE;
                break;
            case TransactionType::DEPOSIT:
                $types['source']      = AccountType::REVENUE;
                $types['destination'] = AccountType::ASSET;
                break;
            case TransactionType::TRANSFER:
                $types['source']      = AccountType::ASSET;
                $types['destination'] = AccountType::ASSET;
                break;
        }
        if (!isset($types[$direction])) {
            throw new FireflyException(sprintf('No type set for direction "%s" and type "%s"', $type, $direction));
        }

        return $types[$direction];
    }

    /**
     * @param string      $expectedType
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return Account
     * @throws FireflyException
     */
    protected function findAccount(string $expectedType, ?int $accountId, ?string $accountName): Account
    {
        $accountId   = intval($accountId);
        $accountName = strval($accountName);
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);

        switch ($expectedType) {
            case AccountType::ASSET:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $repository->findNull($accountId);
                }

                // alternatively, return by name. Validator should catch invalid names.
                return $repository->findByName($accountName, [AccountType::ASSET]);
                break;
            case AccountType::EXPENSE:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $repository->findNull($accountId);
                }
                if (strlen($accountName) > 0) {
                    // alternatively, return by name. Validator should catch invalid names.
                    return $repository->findByName($accountName, [AccountType::EXPENSE]);
                }

                // return cash account:
                return $repository->getCashAccount();
                break;
            case AccountType::REVENUE:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $repository->findNull($accountId);
                }
                if (strlen($accountName) > 0) {
                    // alternatively, return by name. Validator should catch invalid names.
                    return $repository->findByName($accountName, [AccountType::REVENUE]);
                }

                // return cash account:
                return $repository->getCashAccount();

            default:
                throw new FireflyException(sprintf('Cannot find account of type "%s".', $expectedType));

        }
    }

    /**
     * @param int|null    $budgetId
     * @param null|string $budgetName
     *
     * @return Budget|null
     */
    protected function findBudget(?int $budgetId, ?string $budgetName): ?Budget
    {
        $budgetId   = intval($budgetId);
        $budgetName = strval($budgetName);
        if (strlen($budgetName) === 0 && $budgetId === 0) {
            return null;
        }
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budget     = null;
        $repository->setUser($this->user);

        // first by ID:
        if ($budgetId > 0) {
            $budget = $repository->findNull($budgetId);
            if (!is_null($budget)) {
                return $budget;
            }
        }

        if (strlen($budgetName) > 0) {
            $budget = $repository->findByName($budgetName);
            if (!is_null($budget)) {
                return $budget;
            }
        }

        return null;
    }

    /**
     * @param int|null    $categoryId
     * @param null|string $categoryName
     *
     * @return Category|null
     */
    protected function findCategory(?int $categoryId, ?string $categoryName): ?Category
    {
        $categoryId   = intval($categoryId);
        $categoryName = strval($categoryName);

        if (strlen($categoryName) === 0 && $categoryId === 0) {
            return null;
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $category   = null;
        $repository->setUser($this->user);

        // first by ID:
        if ($categoryId > 0) {
            $category = $repository->findNull($categoryId);
            if (!is_null($category)) {
                return $category;
            }
        }

        if (strlen($categoryName) > 0) {
            $category = $repository->findByName($categoryName);
            if (!is_null($category)) {
                return $category;
            }
            // create it?
        }

        return null;
    }

    /**
     * @param int|null    $currencyId
     * @param null|string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    protected function findCurrency(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        $currencyCode = strval($currencyCode);
        $currencyId   = intval($currencyId);

        if (strlen($currencyCode) === 0 && intval($currencyId) === 0) {
            return null;
        }
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $currency   = null;
        $repository->setUser($this->user);

        // first by ID:
        if ($currencyId > 0) {
            $currency = $repository->findNull($currencyId);
            if (!is_null($currency)) {
                return $currency;
            }
        }
        // then by code:
        if (strlen($currencyCode) > 0) {
            $currency = $repository->findByCodeNull($currencyCode);
            if (!is_null($currency)) {
                return $currency;
            }
        }

        return null;
    }
}