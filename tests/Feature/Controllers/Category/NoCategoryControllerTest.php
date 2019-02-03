<?php
/**
 * NoCategoryControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Category;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Navigation;
use Tests\TestCase;

/**
 *
 * Class NoCategoryControllerTest
 */
class NoCategoryControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategory(string $range): void
    {
        Log::info('Test noCategory()');
        // mock stuff
        $collector     = $this->mock(TransactionCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);

        $fiscalHelper->shouldReceive('startOfFiscalYear')->andReturn(new Carbon);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->andReturn(new Carbon);

        // get the journal with the most recent date for firstNull:
        $journal = $this->user()->transactionJournals()->orderBy('date', 'DESC')->first();
        $journalRepos->shouldReceive('firstNull')->twice()->andReturn($journal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     */
    public function testNoCategoryAll(string $range): void
    {
        Log::info('Test nocategoryAll()');
        // mock stuff
        $collector     = $this->mock(TransactionCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);

        $fiscalHelper->shouldReceive('startOfFiscalYear')->andReturn(new Carbon);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->andReturn(new Carbon);

        $journalRepos->shouldReceive('firstNull')->twice()->andReturn(TransactionJournal::first());
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

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
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategoryDate(string $range): void
    {
        Log::info('Test nocategorydate()');
        // mock stuff
        $collector     = $this->mock(TransactionCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $journalRepos->shouldReceive('firstNull')->twice()->andReturn(TransactionJournal::first());
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        Navigation::shouldReceive('updateStartDate')->andReturn(new Carbon);
        Navigation::shouldReceive('updateEndDate')->andReturn(new Carbon);
        Navigation::shouldReceive('startOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('endOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('periodShow')->andReturn('Some date');
        Navigation::shouldReceive('blockPeriods')->andReturn([['period' => '1M', 'start' => new Carbon, 'end' => new Carbon]])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
