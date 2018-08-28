<?php
/**
 * SingleControllerTest.php
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

namespace Tests\Feature\Controllers\Transaction;

use Amount;
use DB;
use Exception;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Steam;
use Tests\TestCase;

/**
 * Class SingleControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testCloneTransaction(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $account = $this->user()->accounts()->first();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getJournalBudgetId')->andReturn(0);
        $journalRepos->shouldReceive('getJournalCategoryName')->andReturn('');
        $journalRepos->shouldReceive('getTags')->andReturn([]);
        $journalRepos->shouldReceive('getMetaField')->andReturnNull();


        $journalRepos->shouldReceive('getNoteText')->andReturn('I see you...')->once();


        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.clone', [$withdrawal->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testCreate(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        Steam::shouldReceive('phpBytes')->andReturn(2048);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        $piggyRepos->shouldReceive('getPiggyBanksWithAmount')->andReturn(new Collection)->once();


        $this->be($this->user());
        $response = $this->get(route('transactions.create', ['withdrawal']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testCreateDepositWithSource(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        Steam::shouldReceive('phpBytes')->andReturn(2048);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        $piggyRepos->shouldReceive('getPiggyBanksWithAmount')->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.create', ['deposit']) . '?source=1');
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testCreateWithSource(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        Steam::shouldReceive('phpBytes')->andReturn(2048);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        $piggyRepos->shouldReceive('getPiggyBanksWithAmount')->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.create', ['withdrawal']) . '?source=1');
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testDelete(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.delete', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testDestroy(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('destroy')->once();
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Withdrawal');


        $this->session(['transactions.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->post(route('transactions.destroy', [$withdrawal->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEdit(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $account = $this->user()->accounts()->first();

        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn(new Transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);

        // mock new account list:
        $currency = TransactionCurrency::first();
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::ASSET, AccountType::DEFAULT, AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN,]])->andReturn(new Collection([$account]))->once();
        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(6);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditCashDeposit(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();

        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $account = $this->user()->accounts()->first();
        $cash    = $this->user()->accounts()->where('account_type_id', 2)->first();

        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Deposit')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$cash]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn(new Transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);

        $this->be($this->user());
        $deposit = Transaction::leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                              ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                              ->where('accounts.account_type_id', 2)
                              ->where('transaction_journals.transaction_type_id', 2)
                              ->whereNull('transaction_journals.deleted_at')
                              ->where('transaction_journals.user_id', $this->user()->id)->first(['transactions.*']);


        $response = $this->get(route('transactions.edit', [$deposit->transaction_journal_id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee(' name="source_name" type="text" value="">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditCashWithdrawal(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();


        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $account = $this->user()->accounts()->first();
        $cash    = $this->user()->accounts()->where('account_type_id', 2)->first();

        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$cash]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn(new Transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);

        $this->be($this->user());
        $withdrawal = Transaction::leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                 ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                 ->where('accounts.account_type_id', 2)
                                 ->where('transaction_journals.transaction_type_id', 1)
                                 ->whereNull('transaction_journals.deleted_at')
                                 ->where('transaction_journals.user_id', $this->user()->id)->first(['transactions.*']);
        $response   = $this->get(route('transactions.edit', [$withdrawal->transaction_journal_id]));

        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee(' name="destination_name" type="text" value="">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditReconcile(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Reconciliation')->once();
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        $this->be($this->user());
        $reconcile = TransactionJournal::where('transaction_type_id', 5)
                                       ->whereNull('transaction_journals.deleted_at')
                                       ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                       ->groupBy('transaction_journals.id')
                                       ->orderBy('ct', 'DESC')
                                       ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);

        $response = $this->get(route('transactions.edit', [$reconcile->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditRedirect(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                        ->groupBy('transaction_journals.id')
                                        ->orderBy('ct', 'DESC')
                                        ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);

        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal');
        $journalRepos->shouldReceive('countTransactions')->andReturn(3);
        $response = $this->get(route('transactions.edit', [$withdrawal->id]));


        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditRedirectOpening(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        $this->be($this->user());
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Opening balance');
        $journalRepos->shouldReceive('countTransactions')->andReturn(3);
        $response = $this->get(route('transactions.edit', [1]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditTransferWithForeignAmount(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();


        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 3)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin(
                                            'transactions', function (JoinClause $join) {
                                            $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('amount', '<', 0);
                                        }
                                        )
                                        ->where('user_id', $this->user()->id)
                                        ->whereNotNull('transactions.foreign_amount')
                                        ->first(['transaction_journals.*']);

        $account = $this->user()->accounts()->first();
        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Transfer')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn(new Transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);

        $response = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditWithForeign(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();

        $account = $this->user()->accounts()->first();
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $transaction                      = new Transaction;
        $transaction->foreign_amount      = '1';
        $transaction->foreign_currency_id = 2;

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn($transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     */
    public function testEditWithForeignAmount(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('setUser')->once();

        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin(
                                            'transactions', function (JoinClause $join) {
                                            $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('amount', '<', 0);
                                        }
                                        )
                                        ->where('user_id', $this->user()->id)
                                        ->whereNotNull('transactions.foreign_amount')
                                        ->first(['transaction_journals.*']);

        $account = $this->user()->accounts()->first();
        $journalRepos->shouldReceive('countTransactions')->andReturn(2)->once();
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal')->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some Note')->once();
        $journalRepos->shouldReceive('getFirstPosTransaction')->andReturn(new Transaction)->once();
        $journalRepos->shouldReceive('getJournalDate')->withAnyArgs()->andReturn('2017-09-01');
        $journalRepos->shouldReceive('getMetaField')->withAnyArgs()->andReturn('')->once();
        $journalRepos->shouldReceive('getJournalCategoryName')->once()->andReturn('');
        $journalRepos->shouldReceive('getJournalBudgetId')->once()->andReturn(0);
        $journalRepos->shouldReceive('getTags')->once()->andReturn([]);


        $response = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers       \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testStoreError(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        // mock results:
        $journal              = new TransactionJournal();
        $journal->description = 'New journal';
        $journalRepos->shouldReceive('store')->andReturn($journal);
        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_id'                 => 1,
            'destination_name'          => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['withdrawal']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers       \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testStoreSuccess(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        // mock results:
        $journal              = new TransactionJournal();
        $journal->id          = 1000;
        $journal->description = 'New journal';
        $journalRepos->shouldReceive('store')->andReturn($journal);
        try {
            $this->expectsEvents(StoredTransactionJournal::class);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $errors = new MessageBag;
        $errors->add('attachments', 'Fake error');

        $messages = new MessageBag;
        $messages->add('attachments', 'Fake error');

        // mock attachment helper, trigger an error AND and info thing.
        $attRepos->shouldReceive('saveAttachmentsForModel');
        $attRepos->shouldReceive('getErrors')->andReturn($errors);
        $attRepos->shouldReceive('getMessages')->andReturn($messages);

        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_id'                 => 1,
            'destination_name'          => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['withdrawal']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('error');
        $response->assertSessionHas('info');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers       \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testStoreSuccessDeposit(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        // mock results:
        $journal              = new TransactionJournal();
        $journal->id          = 1000;
        $journal->description = 'New deposit';
        $journalRepos->shouldReceive('store')->andReturn($journal);
        try {
            $this->expectsEvents(StoredTransactionJournal::class);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $errors = new MessageBag;
        $errors->add('attachments', 'Fake error');

        $messages = new MessageBag;
        $messages->add('attachments', 'Fake error');

        // mock attachment helper, trigger an error AND and info thing.
        $attRepos->shouldReceive('saveAttachmentsForModel');
        $attRepos->shouldReceive('getErrors')->andReturn($errors);
        $attRepos->shouldReceive('getMessages')->andReturn($messages);

        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'deposit',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'destination_id'            => 1,
            'source_name'               => 'Some source',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['deposit']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('error');
        $response->assertSessionHas('info');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers       \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testStoreSuccessTransfer(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        // mock results:
        $journal              = new TransactionJournal();
        $journal->id          = 1000;
        $journal->description = 'New transfer';
        $journalRepos->shouldReceive('store')->andReturn($journal);
        try {
            $this->expectsEvents(StoredTransactionJournal::class);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $errors = new MessageBag;
        $errors->add('attachments', 'Fake error');

        $messages = new MessageBag;
        $messages->add('attachments', 'Fake error');

        // mock attachment helper, trigger an error AND and info thing.
        $attRepos->shouldReceive('saveAttachmentsForModel');
        $attRepos->shouldReceive('getErrors')->andReturn($errors);
        $attRepos->shouldReceive('getMessages')->andReturn($messages);

        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'transfer',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'destination_id'            => 1,
            'source_id'                 => 2,
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['transfer']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('error');
        $response->assertSessionHas('info');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers       \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testStoreSuccessTransferForeign(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);

        // mock results:
        $journal              = new TransactionJournal();
        $journal->id          = 1000;
        $journal->description = 'New transfer';
        $journalRepos->shouldReceive('store')->andReturn($journal);
        try {
            $this->expectsEvents(StoredTransactionJournal::class);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $errors = new MessageBag;
        $errors->add('attachments', 'Fake error');

        $messages = new MessageBag;
        $messages->add('attachments', 'Fake error');

        // mock attachment helper, trigger an error AND and info thing.
        $attRepos->shouldReceive('saveAttachmentsForModel');
        $attRepos->shouldReceive('getErrors')->andReturn($errors);
        $attRepos->shouldReceive('getMessages')->andReturn($messages);

        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                         => 'transfer',
            'amount'                       => '10',
            'source_amount'                => '10',
            'destination_amount'           => '10',
            'amount_currency_id_amount'    => 1,
            'source_account_currency'      => 1,
            'destination_account_currency' => 2,
            'destination_id'               => 1,
            'source_id'                    => 2,
            'date'                         => '2016-01-01',
            'description'                  => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['transfer']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('error');
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController
     * @covers \FireflyIII\Http\Requests\JournalFormRequest
     */
    public function testUpdate(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentHelperInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $linkRepos     = $this->mock(LinkTypeRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal');
        $journalRepos->shouldReceive('getPiggyBankEvents')->andReturn(new Collection);
        $journalRepos->shouldReceive('getMetaField')->andReturn('');

        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection);


        $linkRepos->shouldReceive('get')->andReturn(new Collection);
        $linkRepos->shouldReceive('getLinks')->andReturn(new Collection);
        $attRepos->shouldReceive('saveAttachmentsForModel');
        $attRepos->shouldReceive('getErrors')->andReturn(new MessageBag);
        $attRepos->shouldReceive('getMessages')->andReturn(new MessageBag);

        // mock
        try {
            $this->expectsEvents(UpdatedTransactionJournal::class);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        // find withdrawal:
        $loop = 0;
        do {
            $withdrawal = TransactionJournal::where('transaction_type_id', 1)->inRandomOrder()->where('user_id', $this->user()->id)->first();
            $count      = $withdrawal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        $journalRepos->shouldReceive('update')->andReturn($withdrawal);

        $this->session(['transactions.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'id'                        => 123,
            'what'                      => 'withdrawal',
            'description'               => 'Updated groceries',
            'source_id'                 => 1,
            'destination_name'          => 'PLUS',
            'amount'                    => '123',
            'amount_currency_id_amount' => 1,
            'budget_id'                 => 1,
            'category'                  => 'Daily groceries',
            'tags'                      => '',
            'date'                      => '2016-01-01',
        ];

        $response = $this->post(route('transactions.update', [$withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $response = $this->get(route('transactions.show', [$withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee($withdrawal->description);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
