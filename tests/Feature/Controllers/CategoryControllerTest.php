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
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Steam;
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

        $this->session(['categories.delete.uri' => 'http://localhost']);
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
        $category     = factory(Category::class)->make();
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$category]))->once();
        $repository->shouldReceive('lastUseDate')->andReturn(new Carbon)->once();

        $this->be($this->user());
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
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
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        Steam::shouldReceive('positive')->once()->andReturn('1');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
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
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
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
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
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
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        Steam::shouldReceive('positive')->once()->andReturn('1');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $transaction  = factory(Transaction::class)->make();
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
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->twice();
        $collector->shouldReceive('setRange')->andReturnSelf()->twice();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->twice();
        $collector->shouldReceive('setTypes')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->twice();

        $collector->shouldReceive('getJournals')->andReturn(new Collection)->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->twice();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range)
    {
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->once();

        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1, 'all']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->twice();
        $collector->shouldReceive('setRange')->andReturnSelf()->twice();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->twice();
        $collector->shouldReceive('setTypes')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->twice();
        $collector->shouldReceive('setCategory')->andReturnSelf()->twice();
        $collector->shouldReceive('getJournals')->andReturn(new Collection)->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $repository->shouldReceive('firstUseDate')->once()->andReturn(new Carbon('1900-01-01'));
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('earnedInPeriod')->andReturn('1');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1, '2015-01-01']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowEmpty(string $range)
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
        $collector->shouldReceive('setPage')->andReturnSelf()->times(3);
        $collector->shouldReceive('setLimit')->andReturnSelf()->times(3);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->times(4);
        $collector->shouldReceive('setRange')->andReturnSelf()->times(4);
        $collector->shouldReceive('setTypes')->andReturnSelf()->times(1);
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->times(4);
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->times(3);
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->times(3);
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->times(4);
        $collector->shouldReceive('setCategory')->andReturnSelf()->times(4);

        $collector->shouldReceive('getJournals')->andReturn(new Collection)->times(1);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->times(3);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
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

        $this->session(['categories.create.uri' => 'http://localhost']);

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

        $this->session(['categories.edit.uri' => 'http://localhost']);

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
