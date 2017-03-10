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
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
        // mock stuff
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);


        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['categories.delete.url' => 'http://localhost']);
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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
        // mock stuff
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCategories')->andReturn(new Collection);
        $repository->shouldReceive('lastUseDate')->andReturn(new Carbon);

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
        // mock stuff
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
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
    public function testNoCategoryAll(string $range)
    {
        // mock stuff
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['all']));
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
    public function testNoCategoryDate(string $range)
    {
        // mock stuff
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getGroupedEntries
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        // mock stuff
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('firstUseDate')->once()->andReturn(new Carbon);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('earnedInPeriod')->andReturn('0');

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')->once()->andReturn(new Collection);

        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show.all', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::showByDate
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getGroupedEntries
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        // mock stuff
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

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
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->andReturn(new Category);
        $repository->shouldReceive('store')->andReturn(new Category);

        $this->session(['categories.create.url' => 'http://localhost']);

        $data = [
            'name' => 'New Category ' . rand(1000, 9999),
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::update
     */
    public function testUpdate()
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(new Category);

        $this->session(['categories.edit.url' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Category ' . rand(1000, 9999),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


}
