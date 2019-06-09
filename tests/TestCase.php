<?php

/**
 * TestCase.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests;

use Carbon\Carbon;
use Closure;
use DB;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Log;
use Mockery;
use RuntimeException;

/**
 * Class TestCase
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @param User $user
     * @param string $range
     */
    public function changeDateRange(User $user, $range): void
    {
        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y', 'custom'];
        if (in_array($range, $valid, true)) {
            try {
                Preference::where('user_id', $user->id)->where('name', 'viewRange')->delete();
            } catch (Exception $e) {
                // don't care.
                $e->getMessage();
            }

            Preference::create(
                [
                    'user_id' => $user->id,
                    'name'    => 'viewRange',
                    'data'    => $range,
                ]
            );
            // set period to match?
        }
        if ('custom' === $range) {
            $this->session(
                [
                    'start' => Carbon::now()->subDays(20),
                    'end'   => Carbon::now(),
                ]
            );
        }
    }

    /**
     * @return array
     */
    public function dateRangeProvider(): array
    {
        return [
            'one day'      => ['1D'],
            'one week'     => ['1W'],
            'one month'    => ['1M'],
            'three months' => ['3M'],
            'six months'   => ['6M'],
            'one year'     => ['1Y'],
            'custom range' => ['custom'],
        ];
    }

    /**
     * @return User
     */
    public function demoUser(): User
    {
        throw new FireflyException('demoUser()-method is obsolete.');

        return User::find(4);
    }

    /**
     * @return User
     */
    public function emptyUser(): User
    {
        throw new FireflyException('emptyUser()-method is obsolete.');

        return User::find(2);
    }

    use CreatesApplication;

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
     * @return TransactionJournal
     */
    public function getRandomDeposit(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::DEPOSIT, null);
    }

    /**
     * @return Account
     */
    public function getRandomExpense(): Account
    {
        return $this->getRandomAccount(AccountType::EXPENSE, null);
    }

    /**
     * @return Account
     */
    public function getRandomLoan(): Account
    {
        return $this->getRandomAccount(AccountType::LOAN, null);
    }

    /**
     * @return Account
     */
    public function getRandomRevenue(): Account
    {
        return $this->getRandomAccount(AccountType::REVENUE, null);
    }

    /**
     * @return TransactionJournal
     */
    public function getRandomSplitWithdrawal(): TransactionJournal
    {
        return $this->getRandomSplitJournal(TransactionType::WITHDRAWAL);
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
    public function getRandomWithdrawal(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::WITHDRAWAL);
    }

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return User
     */
    public function user(): User
    {
        return User::find(1);
    }

    /**
     * @return Budget
     */
    protected function getBudget(): Budget
    {
        return $this->user()->budgets()->inRandomOrder()->first();
    }

    /**
     * @return TransactionCurrency
     */
    protected function getEuro(): TransactionCurrency
    {
        return TransactionCurrency::find(1);
    }

    /**
     * @return TransactionGroup
     */
    protected function getRandomWithdrawalGroup(): TransactionGroup
    {
        return $this->getRandomGroup(TransactionType::WITHDRAWAL);
    }

    /**
     * @param string $class
     *
     * @param Closure|null $closure
     *
     * @return \Mockery\MockInterface
     */
    protected function mock($class, Closure $closure = null): \Mockery\MockInterface
    {
        $deprecated = [
            TransactionTransformer::class,
            TransactionCollectorInterface::class,
        ];
        if (in_array($class, $deprecated, true)) {
            throw new RuntimeException(strtoupper('Must not be mocking the transaction collector or transformer.'));
        }
        Log::debug(sprintf('Will now mock %s', $class));
        $object = Mockery::mock($class);
        $this->app->instance($class, $object);

        return $object;
    }

    /**
     * @param string $class
     *
     * @return Mockery\MockInterface
     */
    protected function overload(string $class): \Mockery\MockInterface
    {
        //$this->app->instance($class, $externalMock);
        return Mockery::mock('overload:' . $class);
    }

    /**
     * @param string $type
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
        $result = $query->first(['accounts.*']);

        return $result;
    }

    /**
     * @param string $type
     *
     * @return TransactionGroup
     */
    private function getRandomGroup(string $type): TransactionGroup
    {
        $transactionType = TransactionType::where('type', $type)->first();

        // make sure it's a single count group
        do {
            $journal = $this->user()->transactionJournals()
                            ->where('transaction_type_id', $transactionType->id)->inRandomOrder()->first();
            /** @var TransactionGroup $group */
            $group = $journal->transactionGroup;
            $count = $group->transactionJournals()->count();
            Log::debug(sprintf('Count is %d', $count));
        } while (1 !== $count);

        return $journal->transactionGroup;
    }

    /**
     * @param string $type
     *
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
                'transaction_journalstransaction_type_id',
                DB::raw('COUNT(transaction_journal_id) as ct'),
            ]
        )->first();

        return TransactionJournal::find((int)$result->transaction_journal_id);
    }

    /**
     * @param string $type
     *
     * @return TransactionJournal
     */
    private function getRandomSplitJournal(string $type): TransactionJournal
    {
        $query  = DB::table('transactions')
                    ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                    ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                    ->where('transaction_journals.user_id', $this->user()->id)
                    ->whereNull('transaction_journals.deleted_at')
                    ->whereNull('transactions.deleted_at')
                    ->where('transaction_types.type', $type)
                    ->groupBy('transactions.transaction_journal_id')
                    ->having('ct', '>', 2)
                    ->inRandomOrder()->take(1);
        $result = $query->get(
            [
                'transactions.transaction_journal_id',
                'transaction_journalstransaction_type_id',
                DB::raw('COUNT(transaction_journal_id) as ct'),
            ]
        )->first();

        return TransactionJournal::find((int)$result->transaction_journal_id);
    }
}
