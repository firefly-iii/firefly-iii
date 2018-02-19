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
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var User */
    private $user;

    /**
     * TransactionFactory constructor.
     */
    public function __construct()
    {
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * @param array $data
     *
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        return Transaction::create(
            [
                'reconciled'              => $data['reconciled'],
                'account_id'              => $data['account']->id,
                'transaction_journal_id'  => $data['transaction_journal']->id,
                'description'             => $data['description'],
                'transaction_currency_id' => $data['currency']->id,
                'amount'                  => $data['amount'],
                'foreign_amount'          => $data['foreign_amount'],
                'foreign_currency_id'     => null,
                'identifier'              => $data['identifier'],
            ]
        );
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
        $currency    = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $description = $journal->description === $data['description'] ? null : $data['description'];

        // type of source account depends on journal type:
        $sourceType    = $this->accountType($journal, 'source');
        $sourceAccount = $this->findAccount($sourceType, $data['source_id'], $data['source_name']);

        // same for destination account:
        $destinationType    = $this->accountType($journal, 'destination');
        $destinationAccount = $this->findAccount($destinationType, $data['destination_id'], $data['destination_name']);
        // first make a "negative" (source) transaction based on the data in the array.
        $source = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->negative(strval($data['amount'])),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $sourceAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );
        // then make a "positive" transaction based on the data in the array.
        $dest = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->positive(strval($data['amount'])),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $destinationAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );

        // set foreign currency
        $foreign = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        $this->setForeignCurrency($source, $foreign);
        $this->setForeignCurrency($dest, $foreign);

        // set foreign amount:
        if (!is_null($data['foreign_amount'])) {
            $this->setForeignAmount($source, app('steam')->negative(strval($data['foreign_amount'])));
            $this->setForeignAmount($dest, app('steam')->positive(strval($data['foreign_amount'])));
        }

        // set budget:
        $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
        $this->setBudget($source, $budget);
        $this->setBudget($dest, $budget);

        // set category
        $category = $this->findCategory($data['category_id'], $data['category_name']);
        $this->setCategory($source, $category);
        $this->setCategory($dest, $category);

        return new Collection([$source, $dest]);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
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
            return;
        }
        $transaction->budgets()->save($budget);

        return;
    }

    /**
     * @param Transaction   $transaction
     * @param Category|null $category
     */
    protected function setCategory(Transaction $transaction, ?Category $category): void
    {
        if (is_null($category)) {
            return;
        }
        $transaction->categories()->save($category);

        return;
    }

    /**
     * @param Transaction $transaction
     * @param string      $amount
     */
    protected function setForeignAmount(Transaction $transaction, string $amount): void
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
            return;
        }
        $transaction->foreign_currency_id = $currency->id;
        $transaction->save();

        return;
    }
}