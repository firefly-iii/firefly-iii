<?php
/**
 * TagControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Mockery;
use Preferences;
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testCreate(): void
    {
        $this->mockDefaultSession();
        $this->mock(TagRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

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
        $this->mockDefaultSession();
        $this->mock(TagRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);


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
        $this->mockDefaultSession();
        $repository = $this->mock(TagRepositoryInterface::class);

        $repository->shouldReceive('destroy');
        Preferences::shouldReceive('mark')->atLeast()->once();

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
        $this->mockDefaultSession();
        $this->mock(TagRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('tags.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(TagRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


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
     */
    public function testShow(): void
    {
        $this->mockDefaultSession();

        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        // mock stuff
        $group      = $this->getRandomWithdrawalGroup();
        $paginator  = new LengthAwarePaginator([$group], 1, 40, 1);
        $repository = $this->mock(TagRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $repository->shouldReceive('setUser')->atLeast()->once();
        //$repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('findByTag')->atLeast()->once()->andReturn($this->getRandomTag());


        //Preferences::shouldReceive('mark')->atLeast()->once();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        //$repository->shouldReceive('expenseInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        //$repository->shouldReceive('incomeInPeriod')->andReturn(new Collection)->atLeast()->times(1);
        //$repository->shouldReceive('transferredInPeriod')->andReturn(new Collection)->atLeast()->times(1);

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator)->atLeast()->once();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([]);

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
        $this->mockDefaultSession();

        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        // mock stuff
        $group      = $this->getRandomWithdrawalGroup();
        $paginator  = new LengthAwarePaginator([$group], 1, 40, 1);
        $repository = $this->mock(TagRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('findByTag')->atLeast()->once()->andReturn($this->getRandomTag());

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator)->atLeast()->once();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();

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
        $this->mockDefaultSession();

        $amounts = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::TRANSFER   => '0',
            TransactionType::DEPOSIT    => '0',
        ];

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        // mock stuff
        $helper = $this->mock(FiscalHelperInterface::class);

        $helper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn(new Carbon);
        $helper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn(new Carbon);

        $group      = $this->getRandomWithdrawalGroup();
        $paginator  = new LengthAwarePaginator([$group], 1, 40, 1);
        $repository = $this->mock(TagRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('findByTag')->atLeast()->once()->andReturn($this->getRandomTag());


        //Preferences::shouldReceive('mark')->atLeast()->once();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon)->once();
        $repository->shouldReceive('sumsOfTag')->andReturn($amounts)->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTag')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator)->atLeast()->once();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([]);


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
        $this->mockDefaultSession();
        $repository = $this->mock(TagRepositoryInterface::class);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('findNull')->andReturn(null);
        $repository->shouldReceive('store')->andReturn(new Tag);

        $this->session(['tags.create.uri' => 'http://localhost']);
        $data = [
            'tag'                  => 'Hello new tag' . $this->randomInt(),
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
        $this->mockDefaultSession();
        $repository = $this->mock(TagRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $this->session(['tags.edit.uri' => 'http://localhost']);
        $data = [
            'id'      => 1,
            'tag'     => 'Hello updated tag' . $this->randomInt(),
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
