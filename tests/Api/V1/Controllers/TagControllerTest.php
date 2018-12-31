<?php
/**
 * TagControllerTest.php
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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Transformers\TagTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class TagControllerTest
 */
class TagControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Destroy Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->user()->tags()->inRandomOrder()->first();

        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('destroy')->once()->andReturn(true);
        $tagRepos->shouldReceive('findByTag')->once()->withArgs([(string)$tag->id])->andReturnNull();
        $tagRepos->shouldReceive('findNull')->once()->withArgs([$tag->id])->andReturn($tag);


        // call API
        $response = $this->delete(route('api.v1.tags.delete', [$tag->id]));
        $response->assertStatus(204);
    }

    /**
     * Destroy Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testDeleteByTag(): void
    {
        // mock stuff:
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->user()->tags()->inRandomOrder()->first();
        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('destroy')->once()->andReturn(true);
        $tagRepos->shouldReceive('findByTag')->once()->withArgs([(string)$tag->tag])->andReturn($tag);

        // call API
        $response = $this->delete(route('api.v1.tags.delete', [$tag->tag]));
        $response->assertStatus(204);
    }

    /**
     * Tag index
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testIndex(): void
    {
        // mock stuff:
        $tagRepos    = $this->mock(TagRepositoryInterface::class);
        $transformer = $this->mock(TagTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(1);
        $tagRepos->shouldReceive('get')->once()->andReturn(new Collection());

        // call API
        $response = $this->get(route('api.v1.tags.index'));
        $response->assertStatus(200);
    }

    /**
     * Destroy Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testShow(): void
    {
        // mock stuff:
        $tagRepos    = $this->mock(TagRepositoryInterface::class);
        $tag         = $this->user()->tags()->inRandomOrder()->first();
        $transformer = $this->mock(TagTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('findByTag')->once()->withArgs([(string)$tag->id])->andReturnNull();
        $tagRepos->shouldReceive('findNull')->once()->withArgs([$tag->id])->andReturn($tag);


        // call API
        $response = $this->get(route('api.v1.tags.show', [$tag->id]));
        $response->assertStatus(200);
    }

    /**
     * Show Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testShowByTag(): void
    {
        // mock stuff:
        $tagRepos    = $this->mock(TagRepositoryInterface::class);
        $tag         = $this->user()->tags()->inRandomOrder()->first();
        $transformer = $this->mock(TagTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('findByTag')->once()->withArgs([(string)$tag->tag])->andReturn($tag);

        // call API
        $response = $this->get(route('api.v1.tags.show', [$tag->tag]));
        $response->assertStatus(200);
    }

    /**
     * Create Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     * @covers \FireflyIII\Api\V1\Requests\TagRequest
     */
    public function testStore(): void
    {
        $tagRepos    = $this->mock(TagRepositoryInterface::class);
        $tag         = $this->user()->tags()->inRandomOrder()->first();
        $data        = ['tag' => 'Some tag' . random_int(1, 10000),];
        $transformer = $this->mock(TagTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        $tagRepos->shouldReceive('setUser')->times(1);
        $tagRepos->shouldReceive('store')->times(1)->andReturn($tag);

        // call API
        $response = $this->post(route('api.v1.tags.store'), $data);
        $response->assertStatus(200);
    }

    /**
     * Show transactions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testTransactions(): void
    {
        // mock stuff:
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $tag          = $this->user()->tags()->inRandomOrder()->first();
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $transformer  = $this->mock(TransactionTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        $paginator = new LengthAwarePaginator([], 0, 50);

        // mock calls:
        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('findByTag')->once()->withArgs([(string)$tag->id])->andReturnNull();
        $tagRepos->shouldReceive('findNull')->once()->withArgs([$tag->id])->andReturn($tag);

        $collector->shouldReceive('setUser')->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->once()->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setTag')->once()->andReturnSelf();
        $collector->shouldReceive('removeFilter')->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->once()->andReturn($paginator);


        // call API
        $response = $this->get(route('api.v1.tags.transactions', [$tag->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']));
        $response->assertStatus(200);
    }

    /**
     * Update Tag over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     * @covers \FireflyIII\Api\V1\Requests\TagRequest
     */
    public function testUpdate(): void
    {
        $tagRepos    = $this->mock(TagRepositoryInterface::class);
        $tag         = $this->user()->tags()->inRandomOrder()->first();
        $data        = ['tag' => 'Some tag' . random_int(1, 10000),];
        $transformer = $this->mock(TagTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        $tagRepos->shouldReceive('setUser')->times(2);
        $tagRepos->shouldReceive('update')->times(1)->andReturn($tag);
        $tagRepos->shouldReceive('findByTag')->times(1)->andReturnNull();
        $tagRepos->shouldReceive('findNull')->times(1)->andReturn($tag);

        // call API
        $response = $this->put(route('api.v1.tags.update', [$tag->id]), $data);
        $response->assertStatus(200);
    }

}
