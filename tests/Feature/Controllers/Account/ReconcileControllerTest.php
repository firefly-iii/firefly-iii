<?php
/**
 * ReconcileControllerTest.php
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

namespace Tests\Feature\Controllers\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;
use Mockery;

/**
 * Class ConfigurationControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::edit
     */
    public function testEdit(): void
    {
        $repository  = $this->mock(JournalRepositoryInterface::class);
        $journal     = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $repository->shouldReceive('firstNull')->andReturn($journal);
        $repository->shouldReceive('getFirstPosTransaction')->andReturn($transaction);
        $repository->shouldReceive('getJournalDate')->andReturn('2018-01-01');
        $repository->shouldReceive('getJournalCategoryName')->andReturn('');
        $repository->shouldReceive('getJournalBudgetid')->andReturn(0);

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.edit', [$journal->id]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::edit
     */
    public function testEditRedirect(): void
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.edit', [$journal->id]));
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.edit', [$journal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::overview()
     */
    public function testOverview(): void
    {
        $transactions = $this->user()->transactions()->inRandomOrder()->take(3)->get();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $repository->shouldReceive('getTransactionsById')->andReturn($transactions)->twice();

        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [1, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Account\ReconcileController::overview()
     * @expectedExceptionMessage is not an asset account
     */
    public function testOverviewNotAsset(): void
    {
        $account    = $this->user()->accounts()->where('account_type_id', '!=', 3)->first();
        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [$account->id, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcile(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1, '20170101', '20170131']));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileInitialBalance(): void
    {
        $transaction = Transaction::leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->where('accounts.user_id', $this->user()->id)->where('accounts.account_type_id', 6)->first(['account_id']);
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [$transaction->account_id, '20170101', '20170131']));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNoDates(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNoEndDate(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1, '20170101']));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNotAsset(): void
    {
        $account = $this->user()->accounts()->where('account_type_id', '!=', 6)->where('account_type_id', '!=', 3)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [$account->id, '20170101', '20170131']));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::show()
     */
    public function testShow(): void
    {
        $journal    = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $repository->shouldReceive('getAssetTransaction')->once()->andReturn($journal->transactions()->first());

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.show', [$journal->id]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::show()
     */
    public function testShowSomethingElse(): void
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.show', [$journal->id]));
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$journal->id]));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController::submit()
     * @covers       \FireflyIII\Http\Requests\ReconciliationStoreRequest
     */
    public function testSubmit(): void
    {
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('reconcileById')->andReturn(true);
        $journalRepos->shouldReceive('store')->andReturn(new TransactionJournal);
        $repository->shouldReceive('getReconciliation')->andReturn(new Account);
        $repository->shouldReceive('findNull')->andReturn(new Account);
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(),'currency_id'])->andReturn('1');

        $data = [
            'transactions' => [1, 2, 3],
            'reconcile'    => 'create',
            'difference'   => '5',
            'start'        => '20170101',
            'end'          => '20170131',
        ];
        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.submit', [1, '20170101', '20170131']), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::transactions()
     */
    public function testTransactions(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [1, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::transactions()
     */
    public function testTransactionsInitialBalance(): void
    {
        $transaction = Transaction::leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->where('accounts.user_id', $this->user()->id)->where('accounts.account_type_id', 6)->first(['account_id']);
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [$transaction->account_id, '20170101', '20170131']));
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController::update
     * @covers       \FireflyIII\Http\Requests\ReconciliationUpdateRequest
     */
    public function testUpdate(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection([new Account]));
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection([new Account]));
        $journalRepos->shouldReceive('getNoteText')->andReturn('');
        $journalRepos->shouldReceive('update')->once();

        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $data    = [
            'amount' => '5',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController::update
     * @covers       \FireflyIII\Http\Requests\ReconciliationUpdateRequest
     */
    public function testUpdateNotReconcile(): void
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();
        $data    = [
            'amount' => '5',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$journal->id]));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController::update
     * @covers       \FireflyIII\Http\Requests\ReconciliationUpdateRequest
     */
    public function testUpdateZero(): void
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $data    = [
            'amount' => '0',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }


}
