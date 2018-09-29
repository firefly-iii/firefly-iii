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
use DB;
use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Log;
use Mockery;

/**
 * Class TestCase
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class TestCase extends BaseTestCase
{

    /**
     * @param User   $user
     * @param string $range
     */
    public function changeDateRange(User $user, $range): void
    {
        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y', 'custom'];
        if (\in_array($range, $valid)) {
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

    use CreatesApplication;

    /**
     * @return User
     */
    public function demoUser(): User
    {
        return User::find(4);
    }

    /**
     * @return User
     */
    public function emptyUser(): User
    {
        return User::find(2);
    }

    /**
     * @return Account
     */
    public function getRandomAsset(): Account
    {
        return $this->getRandomAccount(AccountType::ASSET);
    }

    /**
     * @return TransactionJournal
     */
    public function getRandomDeposit(): TransactionJournal
    {
        return $this->getRandomJournal(TransactionType::DEPOSIT);
    }

    /**
     * @return Account
     */
    public function getRandomExpense(): Account
    {
        return $this->getRandomAccount(AccountType::EXPENSE);
    }

    /**
     * @return Account
     */
    public function getRandomRevenue(): Account
    {
        return $this->getRandomAccount(AccountType::REVENUE);
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
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->andReturn(new TransactionJournal);
    }

    /**
     * @return User
     */
    public function user(): User
    {
        return User::find(1);
    }

    /**
     * @param string $class
     *
     * @return \Mockery\MockInterface
     */
    protected function mock($class): \Mockery\MockInterface
    {
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
     * @return Account
     */
    private function getRandomAccount(string $type): Account
    {
        $query  = Account::
        leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                         ->whereNull('accounts.deleted_at')
                         ->where('accounts.user_id', $this->user()->id)
                         ->where('account_types.type', $type)
                         ->inRandomOrder()->take(1);
        $result = $query->first(['accounts.*']);

        return $result;
    }

    /**
     * @param string $type
     *
     * @return TransactionJournal
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
