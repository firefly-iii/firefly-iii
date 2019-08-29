<?php
/**
 * LinkTypeControllerTest.php
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

use Exception;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\LinkTypeTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class LinkTypeControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LinkTypeControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @throws Exception
     */
    public function testStore(): void
    {
        $linkType = LinkType::first();

        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($linkType);
        $userRepository->shouldReceive('hasRole')->once()->andReturn(true);


        // data to submit
        $data = [
            'name'     => 'random' . $this->randomInt(),
            'outward'  => 'outward' . $this->randomInt(),
            'inward'   => 'inward ' . $this->randomInt(),
            'editable' => true,

        ];

        // test API
        $response = $this->post(route('api.v1.link_types.store'), $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @throws Exception
     */
    public function testStoreNotOwner(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $userRepository->shouldReceive('hasRole')->once()->andReturn(false);


        // data to submit
        $data = [
            'name'     => 'random' . $this->randomInt(),
            'outward'  => 'outward' . $this->randomInt(),
            'inward'   => 'inward ' . $this->randomInt(),
            'editable' => true,

        ];

        // test API
        Log::warning('The following error is part of a test.');
        $response = $this->post(route('api.v1.link_types.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You need the \"owner\"-role to do this.');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @throws Exception
     */
    public function testUpdate(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $userRepository->shouldReceive('hasRole')->once()->andReturn(true);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . $this->randomInt(),
                'outward'  => 'outward' . $this->randomInt(),
                'inward'   => 'inward ' . $this->randomInt(),
                'editable' => true,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn($linkType);

        // data to submit
        $data = [
            'name'     => 'random' . $this->randomInt(),
            'outward'  => 'outward' . $this->randomInt(),
            'inward'   => 'inward ' . $this->randomInt(),
            'editable' => true,

        ];

        // test API
        $response = $this->put(route('api.v1.link_types.update', [$linkType->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @throws Exception
     */
    public function testUpdateNotEditable(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . $this->randomInt(),
                'outward'  => 'outward' . $this->randomInt(),
                'inward'   => 'inward ' . $this->randomInt(),
                'editable' => false,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');

        // data to submit
        $data = [
            'name'     => 'random' . $this->randomInt(),
            'outward'  => 'outward' . $this->randomInt(),
            'inward'   => 'inward ' . $this->randomInt(),
            'editable' => true,

        ];

        // test API
        Log::warning('The following error is part of a test.');
        $response = $this->put(route('api.v1.link_types.update', [$linkType->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You cannot edit this link type ');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @throws Exception
     */
    public function testUpdateNotOwner(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        $userRepository->shouldReceive('hasRole')->once()->andReturn(false);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . $this->randomInt(),
                'outward'  => 'outward' . $this->randomInt(),
                'inward'   => 'inward ' . $this->randomInt(),
                'editable' => true,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');

        // data to submit
        $data = [
            'name'     => 'random' . $this->randomInt(),
            'outward'  => 'outward' . $this->randomInt(),
            'inward'   => 'inward ' . $this->randomInt(),
            'editable' => true,

        ];

        // test API
        Log::warning('The following error is part of a test.');
        $response = $this->put(route('api.v1.link_types.update', [$linkType->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You need the \"owner\"-role to do this.');
    }


}
