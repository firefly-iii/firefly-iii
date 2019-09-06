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

use Amount;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Preferences;
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testCreate(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_bills_create');

        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(AttachmentHelperInterface::class);
        $this->mock(BillRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

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
        $this->mockDefaultSession();
        $bill = $this->getRandomBill();

        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(AttachmentHelperInterface::class);
        $this->mock(BillRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('bills.delete', [$bill->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(BillRepositoryInterface::class);
        $this->mock(AttachmentHelperInterface::class);

        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('destroy')->andReturn(true);

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
        $this->mockDefaultSession();

        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $billRepos = $this->mock(BillRepositoryInterface::class);
        $this->mock(AttachmentHelperInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);

        $billRepos->shouldReceive('getNoteText')->andReturn('Hello');
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('bills.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_bills_index');

        Amount::shouldReceive('getDefaultCurrency')->andReturn($this->getEuro());

        // mock stuff
        $this->mock(AttachmentHelperInterface::class);
        $bill        = $this->getRandomBill();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);
        $euro        = $this->getEuro();
        //$pref        = new Preference;
        //$pref->data  = 50;
        //Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Amount::shouldReceive('formatAnything')->andReturn('-100');

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id'                      => 5, 'active' => true,
             'name'                    => 'x', 'next_expected_match' => '2018-01-01',
             'amount_min'              => '10',
             'amount_max'              => '10',
             'currency'                => $this->getEuro(),
             'currency_id'             => $euro->id,
             'currency_code'           => $euro->code,
             'pay_dates'               => [],
             'currency_symbol'         => $euro->symbol,
             'currency_decimal_places' => $euro->decimal_places,
            ]
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collection = new Collection([$bill]);
        $repository->shouldReceive('getBills')->andReturn($collection)->once();
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
        $this->mockDefaultSession();

        // mock stuff
        $rule       = $this->getRandomRule();
        $repository = $this->mock(BillRepositoryInterface::class);
        $this->mock(AttachmentHelperInterface::class);


        $repository->shouldReceive('getRulesForBill')->andReturn(new Collection([$rule]));

        //calls for transaction matcher:
        $matcher = $this->mock(TransactionMatcher::class);
        $matcher->shouldReceive('setSearchLimit')->once()->withArgs([100000]);
        $matcher->shouldReceive('setTriggeredLimit')->once()->withArgs([100000]);
        $matcher->shouldReceive('setRule')->once()->withArgs([Mockery::any()]);
        $matcher->shouldReceive('findTransactionsByRule')->once()->andReturn([]);

        $repository->shouldReceive('linkCollectionToBill')->once();

        Preferences::shouldReceive('mark')->atLeast()->once();


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
        $this->mockDefaultSession();
        $bill = $this->getRandomInactiveBill();
        $this->mock(AttachmentHelperInterface::class);
        $this->mock(BillRepositoryInterface::class);

        $this->be($this->user());
        $response = $this->get(route('bills.rescan', [$bill->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('warning');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController
     */
    public function testShow(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_bills_show');

        // mock stuff
        $repository  = $this->mock(BillRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);
        $collector   = $this->mock(GroupCollectorInterface::class);
        $group       = $this->getRandomWithdrawalGroup();
        $this->mock(AttachmentHelperInterface::class);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $paginator = new LengthAwarePaginator([$group], 1, 40, 1);

        // mock collector:
        $collector->shouldReceive('setBill')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->atLeast()->once()->andReturn($paginator);

        Amount::shouldReceive('formatAnything')->andReturn('-100');

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once();
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once();
        $repository->shouldReceive('getAttachments')->atLeast()->once()->andReturn(new Collection);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            ['id'              => 5, 'active' => true, 'name' => 'x', 'next_expected_match' => '2018-01-01',
             'currency_symbol' => 'x', 'amount_min' => '10', 'amount_max' => '15',
            ]
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $repository->shouldReceive('getYearAverage')->andReturn('0');
        $repository->shouldReceive('getOverallAverage')->andReturn('0');
        $repository->shouldReceive('getRulesForBill')->andReturn(new Collection);

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
        $this->mockDefaultSession();

        $this->be($this->user());
        $bill = $this->user()->bills()->first();
        // mock stuff
        $attachHelper = $this->mock(AttachmentHelperInterface::class);
        $repository   = $this->mock(BillRepositoryInterface::class);

        $repository->shouldReceive('store')->andReturn($bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'name'                    => 'New Bill ' . $this->randomInt(),
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
        $this->mockDefaultSession();

        // mock stuff
        $bill         = $this->getRandomBill();
        $attachHelper = $this->mock(AttachmentHelperInterface::class);
        $repository   = $this->mock(BillRepositoryInterface::class);

        $repository->shouldReceive('store')->andReturn($bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'name'                    => 'New Bill ' . $this->randomInt(),
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
        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(BillRepositoryInterface::class);

        $this->mock(AttachmentHelperInterface::class);
        $repository->shouldReceive('store')->andReturn(null);

        $data = [
            'name'                    => 'New Bill ' . $this->randomInt(),
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
        $this->mockDefaultSession();

        // mock stuff
        $attachHelper = $this->mock(AttachmentHelperInterface::class);
        $repository   = $this->mock(BillRepositoryInterface::class);

        $repository->shouldReceive('store')->andReturn(new Bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'name'                    => 'New Bill ' . $this->randomInt(),
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
        $this->mockDefaultSession();

        // mock stuff
        $attachHelper = $this->mock(AttachmentHelperInterface::class);
        $repository   = $this->mock(BillRepositoryInterface::class);

        $repository->shouldReceive('update')->andReturn(new Bill);
        $attachHelper->shouldReceive('saveAttachmentsForModel');
        $attachHelper->shouldReceive('getMessages')->andReturn(new MessageBag);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'id'                      => 1,
            'name'                    => 'Updated Bill ' . $this->randomInt(),
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
