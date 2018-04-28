<?php
/**
 * SplitControllerTest.php
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

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class SplitControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SplitControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::__construct
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::arrayFromJournal
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::updateWithPrevious
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::getTransactionDataFromJournal
     */
    public function testEdit()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $currencyRepository->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1));



        $deposit              = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $destination          = $deposit->transactions()->where('amount', '>', 0)->first();
        $account              = $destination->account;
        $transactions         = factory(Transaction::class, 3)->make();
        $array                = $transactions->toArray();
        $array[0]['category'] = '';

        $journalRepos->shouldReceive('firstNull')->once()->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Deposit');
        $journalRepos->shouldReceive('getJournalDate')->andReturn('2018-01-01')->once();
        $journalRepos->shouldReceive('getMetaField')->andReturn('');
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some note')->once();
        $journalRepos->shouldReceive('getJournalBudgetId')->andReturn(0);
        $journalRepos->shouldReceive('getCategoryName')->andReturn('');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('0');
        $journalRepos->shouldReceive('getJournalCategoryName')->andReturn('Some');

        // mock for new account list and for account array
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(new Collection([$account]))->twice();

        $currencyRepository->shouldReceive('get')->once()->andReturn(new Collection);
        $budgetRepository->shouldReceive('getActiveBudgets')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$deposit->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::__construct
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::arrayFromJournal
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::updateWithPrevious
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::getTransactionDataFromJournal
     */
    public function testEditOldInput()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $deposit            = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $destination        = $deposit->transactions()->where('amount', '>', 0)->first();
        $account            = $destination->account;
        $transactions       = factory(Transaction::class, 3)->make();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $currencyRepository->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1));



        $currencyRepository->shouldReceive('get')->once()->andReturn(new Collection);
        $budgetRepository->shouldReceive('getActiveBudgets')->andReturn(new Collection);

        // mock for new account list and for account array
        $accountRepos->shouldReceive('getAccountsByType')
                     ->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(new Collection([$account]))->twice();


        $journalRepos->shouldReceive('firstNull')->once()->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Deposit');
        $journalRepos->shouldReceive('getJournalDate')->andReturn('2018-01-01')->once();
        $journalRepos->shouldReceive('getMetaField')->andReturn('');
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some note')->once();
        $journalRepos->shouldReceive('getJournalBudgetId')->andReturn(0);
        $journalRepos->shouldReceive('getCategoryName')->andReturn('');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('0');


        $old = [
            'transactions' => [
                [
                    'transaction_currency_id'     => 1,
                    'transaction_currency_code'   => 'AB',
                    'transaction_currency_symbol' => 'X',
                    'foreign_amount'              => '0',
                    'foreign_currency_id'         => 2,
                    'foreign_currency_code'       => 'CD',
                    'foreign_currency_symbol'     => 'Y',
                ],
                [
                    'transaction_currency_id'     => 1,
                    'transaction_currency_code'   => 'AB',
                    'transaction_currency_symbol' => 'X',
                    'foreign_amount'              => '0',
                    'foreign_currency_id'         => 2,
                    'foreign_currency_code'       => 'CD',
                    'foreign_currency_symbol'     => 'Y',
                ],
                [
                    'transaction_currency_id'     => 1,
                    'transaction_currency_code'   => 'AB',
                    'transaction_currency_symbol' => 'X',
                    'foreign_amount'              => '0',
                    'foreign_currency_id'         => 2,
                    'foreign_currency_code'       => 'CD',
                    'foreign_currency_symbol'     => 'Y',
                ],
                [
                    'transaction_currency_id'     => 1,
                    'transaction_currency_code'   => 'AB',
                    'transaction_currency_symbol' => 'X',
                    'foreign_amount'              => '0',
                    'foreign_currency_id'         => 2,
                    'foreign_currency_code'       => 'CD',
                    'foreign_currency_symbol'     => 'Y',
                ],
                [
                    'transaction_currency_id'     => 1,
                    'transaction_currency_code'   => 'AB',
                    'transaction_currency_symbol' => 'X',
                    'foreign_amount'              => '0',
                    'foreign_currency_id'         => 2,
                    'foreign_currency_code'       => 'CD',
                    'foreign_currency_symbol'     => 'Y',
                ],

            ],
        ];
        $this->session(['_old_input' => $old]);

        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$deposit->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::edit
     */
    public function testEditOpeningBalance()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);

        $opening = TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn($opening);
        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$opening->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::__construct
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::arrayFromJournal
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::updateWithPrevious
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::getTransactionDataFromJournal
     */
    public function testEditSingle()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);

        $transactions = factory(Transaction::class, 1)->make();
        $deposit      = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $destination  = $deposit->transactions()->where('amount', '>', 0)->first();
        $account      = $destination->account;

        $accountRepository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $currencyRepository->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1));


        $journalRepos->shouldReceive('firstNull')->once()->andReturn($deposit);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Deposit');
        $journalRepos->shouldReceive('getJournalDate')->once()->andReturn('2018-01-01');
        $journalRepos->shouldReceive('getMetaField')->andReturn('');
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some note')->once();
        $journalRepos->shouldReceive('getJournalBudgetId')->andReturn(0);
        $journalRepos->shouldReceive('getCategoryName')->andReturn('');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1');

        $currencyRepository->shouldReceive('get')->once()->andReturn(new Collection);
        $budgetRepository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        // mock for new account list and for account array
        $accountRepository->shouldReceive('getAccountsByType')
                          ->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(new Collection([$account]))->twice();

        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$deposit->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController::update
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdate()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);

        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $data    = [
            'id'                             => $deposit->id,
            'what'                           => 'deposit',
            'journal_description'            => 'Updated salary',
            'journal_currency_id'            => 1,
            'journal_destination_account_id' => 1,
            'journal_amount'                 => 1591,
            'date'                           => '2014-01-24',
            'tags'                           => '',
            'transactions'                   => [
                [
                    'transaction_description' => 'Split #1',
                    'source_name'             => 'Job',
                    'transaction_currency_id' => 1,
                    'amount'                  => 1591,
                    'category_name'           => '',
                ],
            ],
        ];

        // mock stuff
        $journalRepos->shouldReceive('update')->andReturn($deposit);
        $journalRepos->shouldReceive('firstNull')->andReturn($deposit);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Deposit');

        $attHelper->shouldReceive('saveAttachmentsForModel');
        $attHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $this->be($this->user());
        $response = $this->post(route('transactions.split.update', [$deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController::update
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController::isOpeningBalance
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateOpeningBalance()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);

        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $opening = TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first();
        $data    = [
            'id'                             => $opening->id,
            'what'                           => 'opening balance',
            'journal_description'            => 'Updated salary',
            'journal_currency_id'            => 1,
            'journal_destination_account_id' => 1,
            'journal_amount'                 => 1591,
            'date'                           => '2014-01-24',
            'tags'                           => '',
            'transactions'                   => [
                [
                    'transaction_description' => 'Split #1',
                    'source_name'             => 'Job',
                    'transaction_currency_id' => 1,
                    'amount'                  => 1591,
                    'category_name'           => '',
                ],
            ],
        ];

        $journalRepos->shouldReceive('firstNull')->once()->andReturn($opening);

        $this->be($this->user());
        $response = $this->post(route('transactions.split.update', [$opening->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionMissing('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController::update
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateTransfer()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);

        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $data     = [
            'id'                        => $transfer->id,
            'what'                      => 'transfer',
            'journal_description'       => 'Some updated withdrawal',
            'journal_currency_id'       => 1,
            'journal_source_account_id' => 1,
            'journal_amount'            => 1591,
            'date'                      => '2014-01-24',
            'tags'                      => '',
            'transactions'              => [
                [
                    'transaction_description' => 'Split #1',
                    'source_account_id'       => '1',
                    'destination_id'          => '2',
                    'transaction_currency_id' => 1,
                    'amount'                  => 1591,
                    'category_name'           => '',
                ],
            ],
        ];

        // mock stuff
        $journalRepos->shouldReceive('update')->andReturn($transfer);
        $journalRepos->shouldReceive('firstNull')->andReturn($transfer);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal');

        $attHelper->shouldReceive('saveAttachmentsForModel');
        $attHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $this->be($this->user());
        $response = $this->post(route('transactions.split.update', [$transfer->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController::update
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateWithdrawal()
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);

        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $withdrawal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->first();
        $data       = [
            'id'                        => $withdrawal->id,
            'what'                      => 'withdrawal',
            'journal_description'       => 'Some updated withdrawal',
            'journal_currency_id'       => 1,
            'journal_source_account_id' => 1,
            'journal_amount'            => 1591,
            'date'                      => '2014-01-24',
            'tags'                      => '',
            'transactions'              => [
                [
                    'transaction_description' => 'Split #1',
                    'source_id'               => '1',
                    'destination_name'        => 'some expense',
                    'transaction_currency_id' => 1,
                    'amount'                  => 1591,
                    'category_name'           => '',
                ],
            ],
        ];

        // mock stuff
        $journalRepos->shouldReceive('update')->andReturn($withdrawal);
        $journalRepos->shouldReceive('firstNull')->andReturn($withdrawal);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Withdrawal');

        $attHelper->shouldReceive('saveAttachmentsForModel');
        $attHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $this->be($this->user());
        $response = $this->post(route('transactions.split.update', [$withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
        $response->assertSessionHas('success');
    }
}
