<?php
/*
 * CollectsValues.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Traits;


use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;

/**
 * Trait CollectsValues
 */
trait CollectsValues
{
    /**
     * @return User
     */
    public function user(): User
    {
        return User::where('email', 'james@firefly')->first();
    }

    /**
     * @return User
     */
    public function nonAdminUser(): User
    {
        return User::where('email', 'no_admin@firefly')->first();
    }

    /**
     * @return Budget
     */
    public function getRandomBudget(): Budget
    {
        return $this->user()->budgets()->inRandomOrder()->first();
    }

    /**
     * @return Category
     */
    public function getRandomCategory(): Category
    {
        return $this->user()->categories()->inRandomOrder()->first();
    }

    /**
     * @return Bill
     */
    public function getRandomBill(): Bill
    {
        return $this->user()->bills()->inRandomOrder()->first();
    }

    /**
     * @return PiggyBank
     */
    public function getRandomPiggyBank(): PiggyBank
    {
        return $this->user()->piggyBanks()->inRandomOrder()->first();
    }


    /**
     * @return Tag
     */
    public function getRandomTag(): Tag
    {
        return $this->user()->tags()->inRandomOrder()->first();
    }

    /**
     * @return TransactionJournal
     */
    public function getRandomWithdrawal(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::WITHDRAWAL);
    }

    /**
     * @return TransactionJournal
     */
    public function getRandomTransfer(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::TRANSFER);
    }

    /**
     * @return TransactionJournal
     */
    public function getRandomDeposit(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::DEPOSIT);
    }

    /**
     * @param string $type
     * @return TransactionJournal
     * @throws FireflyException
     */
    private function getRandomJournal(string $type): TransactionJournal
    {
        $query  = DB::table('transactions')
                    ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                    ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                    ->where('transaction_journals.user_id', $this->user()->id)
                    ->whereNull('transaction_journals.deleted_at')
                    ->whereNull('transactions.deleted_at')
                    ->where('transaction_types.type', $type)
                    ->groupBy('transactions.transaction_journal_id')
                    ->having('ct', '=', 2)
                    ->inRandomOrder()->take(1);
        $result = $query->get(
            [
                'transactions.transaction_journal_id',
                'transaction_journals.transaction_type_id',
                DB::raw('COUNT(transaction_journal_id) as ct'),
            ]
        )->first();
        if (null === $result) {
            throw new FireflyException(sprintf('Cannot find suitable %s to use.', $type));
        }

        return TransactionJournal::find((int) $result->transaction_journal_id);

    }

    /**
     * @return TransactionCurrency
     */
    public function getEuro(): TransactionCurrency
    {
        return TransactionCurrency::whereCode('EUR')->first();
    }

    /**
     * @return TransactionCurrency
     */
    public function getDollar(): TransactionCurrency
    {
        return TransactionCurrency::whereCode('USD')->first();
    }

    /**
     * @param int|null $except
     *
     * @return Account
     */
    public function getRandomAsset(?int $except = null): Account
    {
        return $this->getRandomAccount(AccountType::ASSET, $except);
    }

    /**
     * @param string   $type
     *
     * @param int|null $except
     *
     * @return Account
     */
    private function getRandomAccount(string $type, ?int $except): Account
    {
        $query = Account::
        leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                        ->whereNull('accounts.deleted_at')
                        ->where('accounts.user_id', $this->user()->id)
                        ->where('account_types.type', $type)
                        ->inRandomOrder()->take(1);
        if (null !== $except) {
            $query->where('accounts.id', '!=', $except);
        }
        return $query->first(['accounts.*']);
    }


}
