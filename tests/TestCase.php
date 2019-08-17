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

use Amount;
use Carbon\Carbon;
use Closure;
use DB;
use Exception;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Log;
use Mockery;
use Preferences;
use RuntimeException;

/**
 * Class TestCase
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class TestCase extends BaseTestCase
{

    /**
     * @return ImportJob
     */
    public function getRandomPiggyBankEvent(): PiggyBankEvent
    {
        return PiggyBankEvent::inRandomOrder()->first();
    }

    /**
     * @return ImportJob
     */
    public function getRandomImportJob(): ImportJob
    {
        return $this->user()->importJobs()->inRandomOrder()->first();
    }
    /**
     * @return Recurrence
     */
    public function getRandomRecurrence(): Recurrence
    {
        return $this->user()->recurrences()->inRandomOrder()->first();
    }

    /**
     * @return CurrencyExchangeRate
     */
    public function getRandomCer(): CurrencyExchangeRate
    {
        return $this->user()->currencyExchangeRates()->inRandomOrder()->first();
    }

    /**
     * @return PiggyBank
     */
    public function getRandomPiggyBank(): PiggyBank
    {
        return $this->user()->piggyBanks()->inRandomOrder()->first(['piggy_banks.*']);
    }

    /**
     * @return PiggyBank
     */
    public function getRandomTag(): Tag
    {
        return $this->user()->tags()->inRandomOrder()->first(['tags.*']);
    }

    /**
     * @return Rule
     */
    public function getRandomRule(): Rule
    {
        return $this->user()->rules()->inRandomOrder()->first();
    }

    /**
     * @return Bill
     */
    public function getRandomBill(): Bill
    {
        return $this->user()->bills()->where('active', 1)->inRandomOrder()->first();
    }

    /**
     * @return Bill
     */
    public function getRandomInactiveBill(): Bill
    {
        return $this->user()->bills()->where('active', 0)->inRandomOrder()->first();
    }

    /**
     * @return Attachment
     */
    public function getRandomAttachment(): Attachment
    {
        return $this->user()->attachments()->inRandomOrder()->first();
    }

    /**
     * @return TransactionJournalLink
     */
    public function getRandomLink(): TransactionJournalLink
    {
        return TransactionJournalLink::inRandomOrder()->first();
    }

    /**
     * @return Budget
     */
    public function getRandomBudget(): Budget
    {
        return $this->user()->budgets()->where('active', 1)->inRandomOrder()->first();
    }

    /**
     * @return Category
     */
    public function getRandomCategory(): Category
    {
        return $this->user()->categories()->inRandomOrder()->first();
    }

    /**
     * @return BudgetLimit
     */
    public function getRandomBudgetLimit(): BudgetLimit
    {
        return BudgetLimit
            ::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
            ->where('budgets.user_id', $this->user()->id)
            ->inRandomOrder()->first(['budget_limits.*']);
    }

    /**
     *
     */
    public function mockDefaultSession()
    {
        $this->mockDefaultConfiguration();
        $this->mockDefaultPreferences();
        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrency')->andReturn($euro);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journal       = new TransactionJournal;
        $journal->date = new Carbon;

        $journalRepos->shouldReceive('firstNull')->andReturn($journal);

        return $journalRepos;
    }

    /**
     * Mock the Preferences call that checks if the user has seen the introduction popups already.
     *
     * @param string $key
     */
    public function mockIntroPreference(string $key): void
    {
        $true       = new Preference;
        $true->data = true;
        Preferences::shouldReceive('get')->atLeast()->once()->withArgs([$key, false])->andReturn($true);
    }

    /**
     * Mock the call that checks for the users last activity (for caching).
     */
    public function mockLastActivity(): void
    {
        Preferences::shouldReceive('lastActivity')->withNoArgs()->atLeast()->once()->andReturn('md512345');
    }

    public function mockDefaultConfiguration(): void
    {

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->andReturn($falseConfig);
    }

    /**
     * @return array
     */
    public function getRandomWithdrawalAsArray(): array
    {
        $withdrawal = $this->getRandomWithdrawal();
        $euro       = $this->getEuro();
        $budget     = $this->getRandomBudget();
        $category   = $this->getRandomCategory();
        $expense    = $this->getRandomExpense();
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        return [
            'transaction_group_id'     => $withdrawal->transaction_group_id,
            'transaction_journal_id'   => $withdrawal->id,
            'id'                       => $withdrawal->id,
            'transaction_type_type'    => 'Withdrawal',
            'currency_id'              => $euro->id,
            'foreign_currency_id'      => null,
            'date'                     => $date,
            'description'              => sprintf('I am descr #%d', $this->randomInt()),
            'source_account_id'        => 1,
            'foreign_amount'           => null,
            'destination_account_id'   => $expense->id,
            'destination_account_name' => $expense->name,
            'currency_name'            => $euro->name,
            'currency_code'            => $euro->code,
            'currency_symbol'          => $euro->symbol,

            'currency_decimal_places' => $euro->decimal_places,
            'amount'                  => '-30',
            'budget_id'               => $budget->id,
            'budget_name'             => $budget->name,
            'category_id'             => $category->id,
            'category_name'           => $category->name,
            'tags'                    => ['a', 'b', 'c'],
        ];
    }

    /**
     * @return array
     */
    public function getRandomDepositAsArray(): array
    {
        $deposit  = $this->getRandomDeposit();
        $euro     = $this->getEuro();
        $category = $this->getRandomCategory();
        $revenue  = $this->getRandomRevenue();
        $asset    = $this->getRandomAsset();
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        return [
            'transaction_group_id'     => $deposit->transaction_group_id,
            'transaction_journal_id'   => $deposit->id,
            'transaction_type_type'    => 'Deposit',
            'currency_id'              => $euro->id,
            'foreign_currency_id'      => null,
            'date'                     => $date,
            'description'              => sprintf('I am descr #%d', $this->randomInt()),
            'source_account_id'        => $revenue->id,
            'source_account_name'      => $revenue->name,
            'foreign_amount'           => null,
            'destination_account_id'   => $asset->id,
            'destination_account_name' => $asset->name,
            'currency_name'            => $euro->name,
            'currency_code'            => $euro->code,
            'currency_symbol'          => $euro->symbol,

            'currency_decimal_places' => $euro->decimal_places,
            'amount'                  => '-30',
            'category_id'             => $category->id,
            'category_name'           => $category->name,
        ];
    }


    /**
     * @return array
     */
    public function getRandomTransferAsArray(): array
    {
        $transfer = $this->getRandomTransfer();
        $euro     = $this->getEuro();
        $category = $this->getRandomCategory();
        $source   = $this->getRandomAsset();
        $dest     = $this->getRandomAsset($source->id);
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        return [
            'transaction_group_id'     => $transfer->transaction_group_id,
            'transaction_journal_id'   => $transfer->id,
            'transaction_type_type'    => 'Transfer',
            'currency_id'              => $euro->id,
            'foreign_currency_id'      => null,
            'date'                     => $date,
            'description'              => sprintf('I am descr #%d', $this->randomInt()),
            'source_account_id'        => $source->id,
            'source_account_name'      => $source->name,
            'foreign_amount'           => null,
            'destination_account_id'   => $dest->id,
            'destination_account_name' => $dest->name,
            'currency_name'            => $euro->name,
            'currency_code'            => $euro->code,
            'currency_symbol'          => $euro->symbol,

            'currency_decimal_places' => $euro->decimal_places,
            'amount'                  => '-30',
            'category_id'             => $category->id,
            'category_name'           => $category->name,
        ];
    }

    /**
     * @return array
     */
    public function getRandomWithdrawalGroupAsArray(): array
    {
        $withdrawal = $this->getRandomWithdrawal();
        $euro       = $this->getEuro();
        $budget     = $this->getRandomBudget();
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }

        return
            [
                'group_title'  => null,
                'transactions' => [
                    [
                        'updated_at'              => new Carbon,
                        'created_at'              => new Carbon,
                        'transaction_journal_id'  => $withdrawal->id,
                        'transaction_type_type'   => 'Withdrawal',
                        'currency_id'             => $euro->id,
                        'foreign_currency_id'     => null,
                        'date'                    => $date,
                        'source_id'               => 1,
                        'destination_id'          => 4,
                        'currency_name'           => $euro->name,
                        'currency_code'           => $euro->code,
                        'currency_symbol'         => $euro->symbol,
                        'currency_decimal_places' => $euro->decimal_places,
                        'amount'                  => '-30',
                        'foreign_amount'          => null,
                        'budget_id'               => $budget->id,
                    ],
                ],
            ];
    }

    /**
     * @return array
     */
    public function getRandomDepositGroupAsArray(): array
    {
        $deposit = $this->getRandomDeposit();
        $euro    = $this->getEuro();
        $budget  = $this->getRandomBudget();
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }

        return
            [
                'group_title'  => null,
                'transactions' => [
                    [
                        'updated_at'              => new Carbon,
                        'created_at'              => new Carbon,
                        'transaction_journal_id'  => $deposit->id,
                        'transaction_type_type'   => 'Deposit',
                        'currency_id'             => $euro->id,
                        'foreign_currency_id'     => null,
                        'date'                    => $date,
                        'source_id'               => 1,
                        'destination_id'          => 4,
                        'currency_name'           => $euro->name,
                        'currency_code'           => $euro->code,
                        'currency_symbol'         => $euro->symbol,
                        'currency_decimal_places' => $euro->decimal_places,
                        'amount'                  => '-30',
                        'foreign_amount'          => null,
                        'budget_id'               => $budget->id,
                    ],
                ],
            ];
    }

    /**
     * Mock default preferences.
     */
    public function mockDefaultPreferences(): void
    {
        $false       = new Preference;
        $false->data = false;
        $view        = new Preference;
        $view->data  = '1M';
        $lang        = new Preference;
        $lang->data  = 'en_US';
        $list        = new Preference;
        $list->data  = 50;

        Preferences::shouldReceive('get')->withArgs(['viewRange', Mockery::any()])->andReturn($view);
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($lang);
        Preferences::shouldReceive('get')->withArgs(['list-length', 10])->andReturn($list);
    }

    /**
     * @return int
     */
    public function randomInt(): int
    {
        $result = 4;
        try {
            $result = random_int(1, 100000);
        } catch (Exception $e) {
            Log::debug(sprintf('Could not generate random number: %s', $e->getMessage()));
        }

        return $result;
    }

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
        return User::where('email', 'demo@firefly')->first();
    }

    /**
     * @return User
     */
    public function emptyUser(): User
    {
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
    public function getRandomInitialBalance(): Account
    {
        return $this->getRandomAccount(AccountType::INITIAL_BALANCE, null);
    }

    public function getRandomReconciliation(): Account
    {
        return $this->getRandomAccount(AccountType::RECONCILIATION, null);
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
        return TransactionCurrency::where('code', 'EUR')->first();
    }

    /**
     * @return TransactionCurrency
     */
    protected function getDollar(): TransactionCurrency
    {
        return TransactionCurrency::where('code', 'USD')->first();
    }

    /**
     * @return TransactionGroup
     */
    protected function getRandomWithdrawalGroup(): TransactionGroup
    {
        return $this->getRandomGroup(TransactionType::WITHDRAWAL);
    }

    /**
     * @return TransactionGroup
     */
    protected function getRandomTransferGroup(): TransactionGroup
    {
        return $this->getRandomGroup(TransactionType::TRANSFER);
    }

    /**
     * @return TransactionGroup
     */
    protected function getRandomDepositGroup(): TransactionGroup
    {
        return $this->getRandomGroup(TransactionType::DEPOSIT);
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
        //Log::debug(sprintf('Will now mock %s', $class));
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
            $count = 0;
            if (null !== $group) {
                $count = $group->transactionJournals()->count();
            }
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
        if (null === $result) {
            throw new FireflyException(sprintf('Cannot find suitable %s to use.', $type));
        }

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
