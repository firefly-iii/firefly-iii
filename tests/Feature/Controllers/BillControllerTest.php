<?php
/**
 * BillControllerTest.php
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
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class BillControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BillControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testCreate(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $billRepos      = $this->mock(BillRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('bills.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testDelete(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $billRepos      = $this->mock(BillRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('bills.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testDestroy(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('destroy')->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['bills.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testEdit(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $billRepos      = $this->mock(BillRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $billRepos->shouldReceive('getNoteText')->andReturn('Hello');
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('bills.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testIndex(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $bill           = factory(Bill::class)->make();
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $transformer    = $this->mock(BillTransformer::class);

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5, 'active' => true, 'name' => 'x', 'next_expected_match' => '2018-01-01']
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $collection = new Collection([$bill]);
        $repository->shouldReceive('getPaginator')->andReturn(new LengthAwarePaginator($collection, 1, 50))->once();
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getNoteText')->andReturn('Hi there');
        $repository->shouldReceive('getRulesForBills')->andReturn([]);


        $this->be($this->user());
        $response = $this->get(route('bills.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testRescan(): void
    {
        // mock stuff
        $rule           = Rule::first();
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journal        = factory(TransactionJournal::class)->make();
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getRulesForBill')->andReturn(new Collection([$rule]));

        //calls for transaction matcher:
        // todo bad to do this:
        $matcher = $this->mock(TransactionMatcher::class);
        $matcher->shouldReceive('setSearchLimit')->once()->withArgs([100000]);
        $matcher->shouldReceive('setTriggeredLimit')->once()->withArgs([100000]);
        $matcher->shouldReceive('setRule')->once()->withArgs([Mockery::any()]);
        $matcher->shouldReceive('findTransactionsByRule')->once()->andReturn(new Collection);

        $repository->shouldReceive('linkCollectionToBill')->once();

        $this->be($this->user());
        $response = $this->get(route('bills.rescan', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testRescanInactive(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('bills.rescan', [3]));
        $response->assertStatus(302);
        $response->assertSessionHas('warning');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testShow(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $transformer    = $this->mock(BillTransformer::class);

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once();
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once();
        $repository->shouldReceive('getAttachments')->atLeast()->once()->andReturn(new Collection);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id' => 5, 'active' => true, 'name' => 'x', 'next_expected_match' => '2018-01-01',
                'currency_symbol' => 'x','amount_min' => '10','amount_max' => '15'
                ]
        );


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $repository->shouldReceive('getYearAverage')->andReturn('0');
        $repository->shouldReceive('getOverallAverage')->andReturn('0');
//        $repository->shouldReceive('nextExpectedMatch')->andReturn(new Carbon);
        $repository->shouldReceive('getRulesForBill')->andReturn(new Collection);
//        $repository->shouldReceive('getNoteText')->andReturn('Hi there');
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
//
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setBills')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
//        $repository->shouldReceive('getPaidDatesInRange')->twice()->andReturn(new Collection([new Carbon, new Carbon, new Carbon]));
//        $repository->shouldReceive('setUser');

        $this->be($this->user());
        $response = $this->get(route('bills.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Requests\BillFormRequest
     * @covers \FireflyIII\Http\Requests\Request
     */
    public function testStore(): void
    {
        $this->be($this->user());
        $bill = $this->user()->bills()->first();
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn($bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $data = [
            'name'                    => 'New Bill ' . random_int(1000, 9999),
            'amount_min'              => '100',
            'transaction_currency_id' => 1,
            'skip'                    => 0,
            'strict'                  => 1,
            'amount_max'              => '100',
            'date'                    => '2016-01-01',
            'repeat_freq'             => 'monthly',
        ];
        $this->session(['bills.create.uri' => 'http://localhost']);

        $response = $this->post(route('bills.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Requests\BillFormRequest
     * @covers \FireflyIII\Http\Requests\Request
     */
    public function testStoreCreateAnother(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $bill = $this->user()->bills()->first();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn($bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $data = [
            'name'                    => 'New Bill ' . random_int(1000, 9999),
            'amount_min'              => '100',
            'transaction_currency_id' => 1,
            'skip'                    => 0,
            'create_another'          => '1',
            'strict'                  => 1,
            'amount_max'              => '100',
            'date'                    => '2016-01-01',
            'repeat_freq'             => 'monthly',
        ];
        $this->session(['bills.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Requests\BillFormRequest
     * @covers \FireflyIII\Http\Requests\Request
     */
    public function testStoreError(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(null);

        $data = [
            'name'                    => 'New Bill ' . random_int(1000, 9999),
            'amount_min'              => '100',
            'transaction_currency_id' => 1,
            'skip'                    => 0,
            'strict'                  => 1,
            'amount_max'              => '100',
            'date'                    => '2016-01-01',
            'repeat_freq'             => 'monthly',
        ];
        $this->be($this->user());
        $response = $this->post(route('bills.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('bills.create'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Requests\BillFormRequest
     * @covers \FireflyIII\Http\Requests\Request
     */
    public function testStoreNoGroup(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('store')->andReturn(new Bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $data = [
            'name'                    => 'New Bill ' . random_int(1000, 9999),
            'amount_min'              => '100',
            'transaction_currency_id' => 1,
            'skip'                    => 0,
            'create_another'          => '1',
            'strict'                  => 1,
            'amount_max'              => '100',
            'date'                    => '2016-01-01',
            'repeat_freq'             => 'monthly',
        ];
        $this->session(['bills.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     * @covers \FireflyIII\Http\Requests\BillFormRequest
     * @covers \FireflyIII\Http\Requests\Request
     */
    public function testUpdate(): void
    {
        // mock stuff
        $attachHelper   = $this->mock(AttachmentHelperInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $repository     = $this->mock(BillRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update')->andReturn(new Bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);

        $data = [
            'id'                      => 1,
            'name'                    => 'Updated Bill ' . random_int(1000, 9999),
            'amount_min'              => '100',
            'transaction_currency_id' => 1,
            'skip'                    => 0,
            'amount_max'              => '100',
            'date'                    => '2016-01-01',
            'repeat_freq'             => 'monthly',
        ];
        $this->session(['bills.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
