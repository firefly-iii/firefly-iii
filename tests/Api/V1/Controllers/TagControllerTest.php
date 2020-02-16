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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Transformers\TagTransformer;
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
        $data        = ['tag' => 'Some tag' . $this->randomInt(),];
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
     * @covers \FireflyIII\Api\V1\Controllers\TagController
     */
    public function testCloud(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tags     = $this->user()->tags()->inRandomOrder()->limit(3)->get();

        $tagRepos->shouldReceive('setUser')->times(1);
        $tagRepos->shouldReceive('get')->atLeast()->once()->andReturn($tags);
        $tagRepos->shouldReceive('earnedInPeriod')->times(3)->andReturn('0');
        $tagRepos->shouldReceive('spentInPeriod')->times(3)->andReturn('-10', '-20', '-30');

        // call API
        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-05',
        ];
        $response   = $this->get(route('api.v1.tag-cloud.cloud') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        $response->assertJson(
            [
                'tags' => [
                    [
                        'size'     => 10,
                        'relative' => 0.3333,
                    ],
                    [
                        'size'     => 20,
                        'relative' => 0.6667,
                    ],
                    [
                        'size'     => 30,
                        'relative' => 1,
                    ],
                ],
            ]);
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
        $data        = ['tag' => 'Some tag' . $this->randomInt(),];
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
