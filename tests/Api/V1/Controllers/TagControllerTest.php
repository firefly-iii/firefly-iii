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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));
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
