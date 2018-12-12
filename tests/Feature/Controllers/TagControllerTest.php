<?php
/**
 * TagControllerTest.php
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
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class TagControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TagControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testCreate(): void
    {
        // mock stuff
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('tags.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testDelete(): void
    {
        // mock stuff
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('tags.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testDestroy(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('destroy');

        $this->be($this->user());
        $response = $this->post(route('tags.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testEdit(): void
    {
        // mock stuff
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('tags.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testIndex(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('count')->andReturn(0);
        $repository->shouldReceive('tagCloud')->andReturn([]);
        $repository->shouldReceive('oldestTag')->andReturn(null)->once();
        $repository->shouldReceive('newestTag')->andReturn(null)->once();


        $this->be($this->user());
        $response = $this->get(route('tags.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testShow(): void
    {
        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];

        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $repository->shouldReceive('expenseInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        $repository->shouldReceive('incomeInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        $repository->shouldReceive('transferredInPeriod')->andReturn(new Collection)->atLeast()->times(1);

        $collector->shouldReceive('removeFilter')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        $this->be($this->user());
        $response = $this->get(route('tags.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testShowAll(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();

        $collector->shouldReceive('removeFilter')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $this->be($this->user());
        $response = $this->get(route('tags.show', [1, 'all']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testShowDate(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();

        $repository->shouldReceive('expenseInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        $repository->shouldReceive('incomeInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        $repository->shouldReceive('transferredInPeriod')->andReturn(new Collection)->atLeast()->times(1);


        $collector->shouldReceive('removeFilter')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $this->be($this->user());
        $response = $this->get(route('tags.show', [1, '2016-01-01']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\TagController
     * @covers       \FireflyIII\Http\Requests\TagFormRequest
     */
    public function testStore(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->andReturn(null);
        $repository->shouldReceive('store')->andReturn(new Tag);

        $this->session(['tags.create.uri' => 'http://localhost']);
        $data = [
            'tag'                  => 'Hello new tag' . random_int(999, 10000),
            'tagMode'              => 'nothing',
            'tag_position_has_tag' => 'true',

        ];
        $this->be($this->user());
        $response = $this->post(route('tags.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\TagController
     * @covers       \FireflyIII\Http\Requests\TagFormRequest
     */
    public function testUpdate(): void
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['tags.edit.uri' => 'http://localhost']);
        $data = [
            'id'      => 1,
            'tag'     => 'Hello updated tag' . random_int(999, 10000),
            'tagMode' => 'nothing',
        ];

        $repository->shouldReceive('update');
        $repository->shouldReceive('findNull')->andReturn(Tag::first());

        $this->be($this->user());
        $response = $this->post(route('tags.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
