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
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
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
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController
     */
    public function testEdit(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $transformer        = $this->mock(TransactionTransformer::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);

        $deposit     = $this->getRandomDeposit();
        $destination = $deposit->transactions()->where('amount', '>', 0)->first();
        $account     = $destination->account;

        // mock calls
        $journalRepos->shouldReceive('firstNull')->once()->andReturn($deposit);
        $currencyRepository->shouldReceive('get')->once()->andReturn(new Collection);
        $budgetRepository->shouldReceive('getActiveBudgets')->andReturn(new Collection)->atLeast()->once();
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournals')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->atLeast()->once()->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->atLeast()->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->atLeast()->once();
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Deposit');
        $journalRepos->shouldReceive('getJournalDate')->andReturn('2018-01-01')->once();
        $journalRepos->shouldReceive('getMetaField')->andReturn('')->atLeast()->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some note')->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$deposit->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController
     */
    public function testEditOldInput(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);
        $transformer        = $this->mock(TransactionTransformer::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);

        $deposit     = $this->getRandomDeposit();
        $destination = $deposit->transactions()->where('amount', '>', 0)->first();
        $account     = $destination->account;


        // mock calls
        $journalRepos->shouldReceive('firstNull')->once()->andReturn($deposit);
        $currencyRepository->shouldReceive('get')->once()->andReturn(new Collection);
        $budgetRepository->shouldReceive('getActiveBudgets')->andReturn(new Collection)->atLeast()->once();
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournals')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->atLeast()->once()->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([$account]))->atLeast()->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([$account]))->atLeast()->once();
        $journalRepos->shouldReceive('getTransactionType')->once()->andReturn('Deposit');
        $journalRepos->shouldReceive('getJournalDate')->andReturn('2018-01-01')->once();
        $journalRepos->shouldReceive('getMetaField')->andReturn('')->atLeast()->once();
        $journalRepos->shouldReceive('getNoteText')->andReturn('Some note')->atLeast()->once();

        $old = [
            'transactions' => [
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'AB',
                    'currency_symbol'         => 'X',
                    'foreign_amount'          => '0',
                    'foreign_currency_id'     => 2,
                    'foreign_currency_code'   => 'CD',
                    'foreign_currency_symbol' => 'Y',
                ],
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'AB',
                    'currency_symbol'         => 'X',
                    'foreign_amount'          => '0',
                    'foreign_currency_id'     => 2,
                    'foreign_currency_code'   => 'CD',
                    'foreign_currency_symbol' => 'Y',
                ],
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'AB',
                    'currency_symbol'         => 'X',
                    'foreign_amount'          => '0',
                    'foreign_currency_id'     => 2,
                    'foreign_currency_code'   => 'CD',
                    'foreign_currency_symbol' => 'Y',
                ],
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'AB',
                    'currency_symbol'         => 'X',
                    'foreign_amount'          => '0',
                    'foreign_currency_id'     => 2,
                    'foreign_currency_code'   => 'CD',
                    'foreign_currency_symbol' => 'Y',
                ],
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'AB',
                    'currency_symbol'         => 'X',
                    'foreign_amount'          => '0',
                    'foreign_currency_id'     => 2,
                    'foreign_currency_code'   => 'CD',
                    'foreign_currency_symbol' => 'Y',
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
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController
     */
    public function testEditOpeningBalance(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);


        $opening = TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn($opening);
        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$opening->id]));
        $response->assertStatus(302);
    }


    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdate(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);


        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $data    = [
            'id'                     => $deposit->id,
            'what'                   => 'deposit',
            'journal_description'    => 'Updated salary',
            'journal_currency_id'    => 1,
            'journal_destination_id' => 1,
            'journal_amount'         => 1591,
            'date'                   => '2014-01-24',
            'tags'                   => '',
            'transactions'           => [
                [
                    'transaction_description' => 'Split #1',
                    'source_name'             => 'Job',
                    'currency_id'             => 1,
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
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateOpeningBalance(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $opening = TransactionJournal::where('transaction_type_id', 4)->where('user_id', $this->user()->id)->first();
        $data    = [
            'id'                     => $opening->id,
            'what'                   => 'opening balance',
            'journal_description'    => 'Updated salary',
            'journal_currency_id'    => 1,
            'journal_destination_id' => 1,
            'journal_amount'         => 1591,
            'date'                   => '2014-01-24',
            'tags'                   => '',
            'transactions'           => [
                [
                    'transaction_description' => 'Split #1',
                    'source_name'             => 'Job',
                    'currency_id'             => 1,
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
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateTransfer(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);

        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $data     = [
            'id'                  => $transfer->id,
            'what'                => 'transfer',
            'journal_description' => 'Some updated withdrawal',
            'journal_currency_id' => 1,
            'journal_source_id'   => 1,
            'journal_amount'      => 1591,
            'date'                => '2014-01-24',
            'tags'                => '',
            'transactions'        => [
                [
                    'transaction_description' => 'Split #1',
                    'source_id'               => '1',
                    'destination_id'          => '2',
                    'currency_id'             => 1,
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
     * @covers       \FireflyIII\Http\Controllers\Transaction\SplitController
     * @covers       \FireflyIII\Http\Requests\SplitJournalFormRequest
     */
    public function testUpdateWithdrawal(): void
    {
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos       = $this->mock(JournalRepositoryInterface::class);
        $attHelper          = $this->mock(AttachmentHelperInterface::class);
        $ruleRepos          = $this->mock(RuleGroupRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $userRepos          = $this->mock(UserRepositoryInterface::class);


        $billRepos->shouldReceive('scan');
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getActiveGroups')->andReturn(new Collection);


        $this->session(['transactions.edit-split.uri' => 'http://localhost']);
        $withdrawal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->first();
        $data       = [
            'id'                  => $withdrawal->id,
            'what'                => 'withdrawal',
            'journal_description' => 'Some updated withdrawal',
            'journal_currency_id' => 1,
            'journal_source_id'   => 1,
            'journal_amount'      => 1591,
            'date'                => '2014-01-24',
            'tags'                => '',
            'transactions'        => [
                [
                    'transaction_description' => 'Split #1',
                    'source_id'               => '1',
                    'destination_name'        => 'some expense',
                    'currency_id'             => 1,
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
