<?php
/**
 * AccountControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.create', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::destroy
     */
    public function testDestroy()
    {
        $this->session(['accounts.delete.url' => 'http://localhost/accounts/show/1']);

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->withArgs([0])->once()->andReturn(new Account);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('accounts.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::index
     * @covers       \FireflyIII\Http\Controllers\AccountController::__construct
     * @covers       \FireflyIII\Http\Controllers\AccountController::isInArray
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.index', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::show
     * @covers       \FireflyIII\Http\Controllers\AccountController::periodEntries
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('amountOutInPeriod')->withAnyArgs()->andReturn('-1');
        $tasker->shouldReceive('amountInInPeriod')->withAnyArgs()->andReturn('1');

        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date);
        $repository->shouldReceive('getAccountsByType')->andReturn(new Collection);


        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::showAll
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show.all', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\AccountController::showByDate
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show.date', [1, '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::store
     */
    public function testStore()
    {
        $this->session(['accounts.create.url' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name' => 'new account ' . rand(1000, 9999),
            'what' => 'asset',
        ];

        $response = $this->post(route('accounts.store', ['asset']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // list should have this new account.
        $response = $this->get(route('accounts.index', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AccountController::update
     */
    public function testUpdate()
    {
        $this->session(['accounts.edit.url' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'name'   => 'updated account ' . rand(1000, 9999),
            'active' => 1,
            'what'   => 'asset',
        ];

        $response = $this->post(route('accounts.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // list should have this new account.
        $response = $this->get(route('accounts.index', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }
}
