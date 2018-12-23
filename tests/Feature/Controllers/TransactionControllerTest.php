<?php
/**
 * TransactionControllerTest.php
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

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class TransactionControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndex(): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff
        $transfer     = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $repository->shouldReceive('firstNull')->twice()->andReturn($transfer);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndexAll(): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff
        $transfer     = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $repository->shouldReceive('firstNull')->twice()->andReturn($transfer);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index.all', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndexByDate(): void
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);


        // mock stuff
        $transfer     = $this->getRandomTransfer();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $date = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer', '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndexByDateReversed(): void
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);


        // mock stuff
        $transfer     = $this->getRandomTransfer();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $date = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer', '2016-01-01', '2015-12-31']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndexDeposit(): void
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);

        // mock stuff
        $transfer     = $this->getRandomTransfer();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['deposit']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testIndexWithdrawal(): void
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);

        // mock stuff
        $transfer     = $this->getRandomTransfer();
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['withdrawal']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testReconcile(): void
    {
        $data         = ['transactions' => [1, 2]];
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $repository->shouldReceive('firstNull')->times(1)->andReturn(new TransactionJournal);
        $repository->shouldReceive('findTransaction')->andReturn(new Transaction)->twice();
        $repository->shouldReceive('reconcile')->twice();

        $this->be($this->user());
        $response = $this->post(route('transactions.reconcile'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testReorder(): void
    {
        // mock stuff
        $journal       = factory(TransactionJournal::class)->make();
        $journal->date = new Carbon('2016-01-01');
        $repository    = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $attRepos      = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $repository->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->once()->andReturn($journal);
        $repository->shouldReceive('setOrder')->once()->andReturn(true);

        $data = [
            'date'  => '2016-01-01',
            'items' => [1],
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.reorder'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testShow(): void
    {
        // mock stuff
        $linkRepos    = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $transformer  = $this->mock(TransactionTransformer::class);
        $attachment   = new Attachment;
        $transaction = new Transaction;

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id' => 5,
            ]
        );

        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournals')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->atLeast()->once()->andReturn(new Collection([$transaction, $transaction]));

        $linkRepos->shouldReceive('get')->andReturn(new Collection);
        $linkRepos->shouldReceive('getLinks')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal)->atLeast()->once();
        $journalRepos->shouldReceive('getAttachments')->andReturn(new Collection([$attachment]))->atLeast()->once();
        $journalRepos->shouldReceive('getPiggyBankEvents')->andReturn(new Collection)->atLeast()->once();
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->atLeast()->once();
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->atLeast()->once();
        $journalRepos->shouldReceive('getMetaField')->andReturn('')->atLeast()->once();

        $attRepos->shouldReceive('exists')->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('transactions.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Controller
     * @covers \FireflyIII\Http\Controllers\TransactionController
     */
    public function testShowOpeningBalance(): void
    {
        $linkRepos    = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $attRepos     = $this->mock(AttachmentRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $linkRepos->shouldReceive('get')->andReturn(new Collection);
        $linkRepos->shouldReceive('getLinks')->andReturn(new Collection);

        $this->be($this->user());
        $journal  = $this->user()->transactionJournals()->where('transaction_type_id', 4)->first();
        $response = $this->get(route('transactions.show', [$journal->id]));
        $response->assertStatus(302);
    }
}
