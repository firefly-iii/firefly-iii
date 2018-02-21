<?php
/**
 * TransactionUpdateService.php
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

namespace FireflyIII\Services\Internal;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;

/**
 * Class TransactionUpdateService
 */
class TransactionUpdateService
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var User */
    private $user;

    public function __construct()
    {
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
    }

    /**
     * @param Transaction $transaction
     * @param array       $data
     *
     * @return Transaction
     * @throws FireflyException
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $currency    = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $journal     = $transaction->transactionJournal;
        $description = $journal->description === $data['description'] ? null : $data['description'];

        // update description:
        $transaction->description = $description;
        if (floatval($transaction->amount) < 0) {
            // this is the source transaction.
            $type          = $this->accountType($journal, 'source');
            $account       = $this->findAccount($type, $data['source_id'], $data['source_name']);
            $amount        = app('steam')->negative(strval($data['amount']));
            $foreignAmount = app('steam')->negative(strval($data['foreign_amount']));
        }

        if (floatval($transaction->amount) > 0) {
            // this is the destination transaction.
            $type          = $this->accountType($journal, 'destination');
            $account       = $this->findAccount($type, $data['destination_id'], $data['destination_name']);
            $amount        = app('steam')->positive(strval($data['amount']));
            $foreignAmount = app('steam')->positive(strval($data['foreign_amount']));
        }

        // update the actual transaction:
        $transaction->description             = $description;
        $transaction->amount                  = $amount;
        $transaction->foreign_amount          = null;
        $transaction->transaction_currency_id = $currency->id;
        $transaction->account_id              = $account->id;
        $transaction->reconciled              = $data['reconciled'];
        $transaction->save();

        // set foreign currency
        $foreign = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        // set foreign amount:
        if (!is_null($data['foreign_amount'])) {
            $this->setForeignCurrency($transaction, $foreign);
            $this->setForeignAmount($transaction, $foreignAmount);
        }
        if (is_null($data['foreign_amount'])) {
            $this->setForeignCurrency($transaction, null);
            $this->setForeignAmount($transaction, null);
        }

        // set budget:
        $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
        $this->setBudget($transaction, $budget);

        // set category
        $category = $this->findCategory($data['category_id'], $data['category_name']);
        $this->setCategory($transaction, $category);

        return $transaction;
    }

    /**
     * TODO this method is duplicated
     *
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
     * TODO this method is duplicated.
     *
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

        switch ($expectedType) {
            case AccountType::ASSET:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $this->accountRepository->findNull($accountId);
                }

                // alternatively, return by name. Validator should catch invalid names.
                return $this->accountRepository->findByName($accountName, [AccountType::ASSET]);
                break;
            case AccountType::EXPENSE:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $this->accountRepository->findNull($accountId);
                }
                if (strlen($accountName) > 0) {
                    /** @var AccountFactory $factory */
                    $factory = app(AccountFactory::class);
                    $factory->setUser($this->user);

                    return $factory->findOrCreate($accountName, AccountType::EXPENSE);
                }

                // return cash account:
                return $this->accountRepository->getCashAccount();
                break;
            case AccountType::REVENUE:
                if ($accountId > 0) {
                    // must be able to find it based on ID. Validator should catch invalid ID's.
                    return $this->accountRepository->findNull($accountId);
                }
                if (strlen($accountName) > 0) {
                    // alternatively, return by name.
                    /** @var AccountFactory $factory */
                    $factory = app(AccountFactory::class);
                    $factory->setUser($this->user);

                    return $factory->findOrCreate($accountName, AccountType::REVENUE);
                }

                // return cash account:
                return $this->accountRepository->getCashAccount();

            default:
                throw new FireflyException(sprintf('Cannot find account of type "%s".', $expectedType));

        }
    }

    /**
     * TODO method is duplicated
     *
     * @param int|null    $budgetId
     * @param null|string $budgetName
     *
     * @return Budget|null
     */
    protected function findBudget(?int $budgetId, ?string $budgetName): ?Budget
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user);

        return $factory->find($budgetId, $budgetName);
    }

    /**
     * TODO method is duplicated
     *
     * @param int|null    $categoryId
     * @param null|string $categoryName
     *
     * @return Category|null
     */
    protected function findCategory(?int $categoryId, ?string $categoryName): ?Category
    {
        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate($categoryId, $categoryName);
    }

    /**
     * TODO method is duplicated
     *
     * @param int|null    $currencyId
     * @param null|string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    protected function findCurrency(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        $factory = app(TransactionCurrencyFactory::class);

        return $factory->find($currencyId, $currencyCode);
    }

    /**
     * TODO almost the same as in transaction factory.
     *
     * @param Transaction $transaction
     * @param Budget|null $budget
     */
    protected function setBudget(Transaction $transaction, ?Budget $budget): void
    {
        if (is_null($budget)) {
            return;
        }
        $transaction->budgets()->sync([$budget->id]);

        return;
    }

    /**
     * TODO almost the same as in transaction factory.
     *
     * @param Transaction   $transaction
     * @param Category|null $category
     */
    protected function setCategory(Transaction $transaction, ?Category $category): void
    {
        if (is_null($category)) {
            return;
        }
        $transaction->categories()->sync([$category->id]);

        return;
    }

    /**
     * TODO method is duplicated
     *
     * @param Transaction $transaction
     * @param string|null      $amount
     */
    protected function setForeignAmount(Transaction $transaction, ?string $amount): void
    {
        $transaction->foreign_amount = $amount;
        $transaction->save();
    }

    /**
     * TODO method is duplicated
     *
     * @param Transaction              $transaction
     * @param TransactionCurrency|null $currency
     */
    protected function setForeignCurrency(Transaction $transaction, ?TransactionCurrency $currency): void
    {
        if (is_null($currency)) {
            $transaction->foreign_currency_id = null;
            $transaction->save();
            return;
        }
        $transaction->foreign_currency_id = $currency->id;
        $transaction->save();

        return;
    }


}