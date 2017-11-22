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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * @covers \FireflyIII\Http\Controllers\TagController::create
     */
    public function testCreate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('tags.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('tags.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('destroy');

        $this->be($this->user());
        $response = $this->post(route('tags.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('tags.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::index
     * @covers \FireflyIII\Http\Controllers\TagController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('count')->andReturn(0);
        $repository->shouldReceive('tagCloud')->andReturn([]);
        $repository->shouldReceive('oldestTag')->andReturn(null)->once();

        $this->be($this->user());
        $response = $this->get(route('tags.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::show
     * @covers \FireflyIII\Http\Controllers\TagController::getPeriodOverview
     */
    public function testShow()
    {
        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];

        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('spentInPeriod')->andReturn('-1')->once();
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('lastUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('earnedInPeriod')->andReturn('1')->once();
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $collector->shouldReceive('removeFilter')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

        $this->be($this->user());
        $response = $this->get(route('tags.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::show
     */
    public function testShowAll()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
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
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

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
     * @covers \FireflyIII\Http\Controllers\TagController::show
     */
    public function testShowDate()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('spentInPeriod')->andReturn('-1')->once();
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('lastUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('earnedInPeriod')->andReturn('1')->once();

        $collector->shouldReceive('removeFilter')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->once();

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
     * @covers \FireflyIII\Http\Controllers\TagController::store
     */
    public function testStore()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->andReturn(new Tag);
        $repository->shouldReceive('store')->andReturn(new Tag);

        $this->session(['tags.create.uri' => 'http://localhost']);
        $data = [
            'tag'     => 'Hello new tag' . rand(999, 10000),
            'tagMode' => 'nothing',
        ];
        $this->be($this->user());
        $response = $this->post(route('tags.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $repository   = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['tags.edit.uri' => 'http://localhost']);
        $data = [
            'tag'     => 'Hello updated tag' . rand(999, 10000),
            'tagMode' => 'nothing',
        ];

        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(new Tag);

        $this->be($this->user());
        $response = $this->post(route('tags.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
