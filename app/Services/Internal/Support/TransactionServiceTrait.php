<?php
/**
 * TransactionServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;


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
use Log;

/**
 * Trait TransactionServiceTrait
 *
 * @package FireflyIII\Services\Internal\Support
 */
trait TransactionServiceTrait
{

    /**
     * @param TransactionJournal $journal
     * @param string             $direction
     *
     * @return string|null
     */
    public function accountType(TransactionJournal $journal, string $direction): ?string
    {
        $types = [];
        $type  = $journal->transactionType->type;
        switch ($type) {
            default:
                // @codeCoverageIgnoreStart
                Log::error(sprintf('Cannot handle type "%s" in accountType()', $type));

                return null;
            // @codeCoverageIgnoreEnd
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
            case TransactionType::RECONCILIATION:
                // always NULL, since this is handled by the reconciliation.
                $types['source']      = null;
                $types['destination'] = null;

                // return here:
                return $types[$direction];
        }
        if (!isset($types[$direction])) {
            // @codeCoverageIgnoreStart
            Log::error(sprintf('No type set for direction "%s" and type "%s"', $type, $direction));

            return null;
            // @codeCoverageIgnoreEnd
        }

        return $types[$direction];
    }

    /**
     * @param string|null $expectedType
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return Account
     */
    public function findAccount(?string $expectedType, ?int $accountId, ?string $accountName): Account
    {
        $accountId   = intval($accountId);
        $accountName = strval($accountName);
        $repository  = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);

        if (is_null($expectedType)) {
            return $repository->findNull($accountId);
        }

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
                    /** @var AccountFactory $factory */
                    $factory = app(AccountFactory::class);
                    $factory->setUser($this->user);

                    return $factory->findOrCreate($accountName, AccountType::EXPENSE);
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
                    // alternatively, return by name.
                    /** @var AccountFactory $factory */
                    $factory = app(AccountFactory::class);
                    $factory->setUser($this->user);

                    return $factory->findOrCreate($accountName, AccountType::REVENUE);
                }

                // return cash account:
                return $repository->getCashAccount();

            default:
                // @codeCoverageIgnoreStart
                Log::error(sprintf('Cannot find account of type "%s".', $expectedType));

                return null;
            // @codeCoverageIgnoreEnd

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
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user);

        return $factory->find($budgetId, $budgetName);
    }

    /**
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
     * @param Transaction $transaction
     * @param Budget|null $budget
     */
    protected function setBudget(Transaction $transaction, ?Budget $budget): void
    {
        if (is_null($budget)) {
            $transaction->budgets()->sync([]);

            return;
        }
        $transaction->budgets()->sync([$budget->id]);

        return;
    }


    /**
     * @param Transaction   $transaction
     * @param Category|null $category
     */
    protected function setCategory(Transaction $transaction, ?Category $category): void
    {
        if (is_null($category)) {
            $transaction->categories()->sync([]);

            return;
        }
        $transaction->categories()->sync([$category->id]);

        return;
    }


    /**
     * @param Transaction $transaction
     * @param string|null $amount
     */
    protected function setForeignAmount(Transaction $transaction, ?string $amount): void
    {
        $transaction->foreign_amount = $amount;
        $transaction->save();
    }

    /**
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
