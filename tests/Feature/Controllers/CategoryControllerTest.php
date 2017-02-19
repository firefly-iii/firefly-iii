<?php
/**
 * CategoryControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('categories.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('categories.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::destroy
     */
    public function testDestroy()
    {
        $this->session(['categories.delete.url' => 'http://localhost']);
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('categories.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('categories.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::index
     * @covers \FireflyIII\Http\Controllers\CategoryController::__construct
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategory(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {

        $collector     = $this->mock(JournalCollectorInterface::class);
        $accRepository = $this->mock(AccountRepositoryInterface::class);
        $catRepository = $this->mock(CategoryRepositoryInterface::class);

        $accRepository->shouldReceive('getAccountsByType')->once()->andReturn(new Collection);
        $catRepository->shouldReceive('firstUseDate')->once()->andReturn(new Carbon);

        // collector stuff:
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        // more repos stuff:
        $catRepository->shouldReceive('spentInPeriod')->andReturn('0');
        $catRepository->shouldReceive('earnedInPeriod')->andReturn('0');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::showAll
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range)
    {
        $collector     = $this->mock(JournalCollectorInterface::class);

        // collector stuff:
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show.all', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::showByDate
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        $collector     = $this->mock(JournalCollectorInterface::class);

        // collector stuff:
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        // mock category repository
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('firstUseDate')->once()->andReturn(new Carbon);
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('earnedInPeriod')->andReturn('1');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show.date', [1, '2015-01-01']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::store
     */
    public function testStore()
    {
        $this->session(['categories.create.url' => 'http://localhost']);

        $data = [
            'name' => 'New Category ' . rand(1000, 9999),
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // must be in list
        $this->be($this->user());
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::update
     */
    public function testUpdate()
    {
        $this->session(['categories.edit.url' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Category ' . rand(1000, 9999),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // must be in list
        $this->be($this->user());
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }


}
