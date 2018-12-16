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

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\LinkTypeTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;


/**
 *
 * Class LinkTypeControllerTest
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
        Log::info(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . random_int(1, 100000),
                'outward'  => 'outward' . random_int(1, 100000),
                'inward'   => 'inward ' . random_int(1, 100000),
                'editable' => true,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // call API
        $response = $this->delete('/api/v1/link_types/' . $linkType->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     */
    public function testDeleteNotEditable(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . random_int(1, 100000),
                'outward'  => 'outward' . random_int(1, 100000),
                'inward'   => 'inward ' . random_int(1, 100000),
                'editable' => false,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->delete('/api/v1/link_types/' . $linkType->id);
        $response->assertStatus(500);
        $response->assertSee('You cannot delete this link type');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     */
    public function testIndex(): void
    {
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('get')->once()->andReturn(new Collection);

        // call API
        $response = $this->get('/api/v1/link_types');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     */
    public function testShow(): void
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

        // call API
        $response = $this->get('/api/v1/link_types/' . $linkType->id);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @covers \FireflyIII\Api\V1\Requests\LinkTypeRequest
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
            'name'     => 'random' . random_int(1, 100000),
            'outward'  => 'outward' . random_int(1, 100000),
            'inward'   => 'inward ' . random_int(1, 100000),
            'editable' => true,

        ];

        // test API
        $response = $this->post('/api/v1/link_types', $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @covers \FireflyIII\Api\V1\Requests\LinkTypeRequest
     */
    public function testStoreNotOwner(): void
    {
        $linkType = LinkType::first();

        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $userRepository->shouldReceive('hasRole')->once()->andReturn(false);


        // data to submit
        $data = [
            'name'     => 'random' . random_int(1, 100000),
            'outward'  => 'outward' . random_int(1, 100000),
            'inward'   => 'inward ' . random_int(1, 100000),
            'editable' => true,

        ];

        // test API
        $response = $this->post('/api/v1/link_types', $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You need the \"owner\"-role to do this.');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     */
    public function testTransactions(): void
    {
        $linkType  = LinkType::first();
        $paginator = new LengthAwarePaginator(new Collection, 0, 50);
        // mock repositories:
        $linkTypeRepos = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $collector     = $this->mock(TransactionCollectorInterface::class);
        $transformer   = $this->mock(TransactionTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        $journalIds = $linkTypeRepos->shouldReceive('getJournalIds')->once()->andReturn([1, 2, 3]);
        $collector->shouldReceive('setUser')->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->once()->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setJournalIds')->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->once()->andReturn($paginator);

        $collector->shouldReceive('removeFilter')->once()->andReturnSelf();
        $linkTypeRepos->shouldReceive('setUser')->once();

        $response = $this->get(
            route('api.v1.link_types.transactions', [$linkType->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31'])
        );
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @covers \FireflyIII\Api\V1\Requests\LinkTypeRequest
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
                'name'     => 'random' . random_int(1, 100000),
                'outward'  => 'outward' . random_int(1, 100000),
                'inward'   => 'inward ' . random_int(1, 100000),
                'editable' => true,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn($linkType);

        // data to submit
        $data = [
            'name'     => 'random' . random_int(1, 100000),
            'outward'  => 'outward' . random_int(1, 100000),
            'inward'   => 'inward ' . random_int(1, 100000),
            'editable' => true,

        ];

        // test API
        $response = $this->put('/api/v1/link_types/' . $linkType->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @covers \FireflyIII\Api\V1\Requests\LinkTypeRequest
     */
    public function testUpdateNotEditable(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . random_int(1, 100000),
                'outward'  => 'outward' . random_int(1, 100000),
                'inward'   => 'inward ' . random_int(1, 100000),
                'editable' => false,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');

        // data to submit
        $data = [
            'name'     => 'random' . random_int(1, 100000),
            'outward'  => 'outward' . random_int(1, 100000),
            'inward'   => 'inward ' . random_int(1, 100000),
            'editable' => true,

        ];

        // test API
        $response = $this->put('/api/v1/link_types/' . $linkType->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You cannot edit this link type ');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\LinkTypeController
     * @covers \FireflyIII\Api\V1\Requests\LinkTypeRequest
     */
    public function testUpdateNotOwner(): void
    {
        // mock stuff:
        $repository     = $this->mock(LinkTypeRepositoryInterface::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $transformer    = $this->mock(LinkTypeTransformer::class);

        $userRepository->shouldReceive('hasRole')->once()->andReturn(false);

        // create editable link type:
        $linkType = LinkType::create(
            [
                'name'     => 'random' . random_int(1, 100000),
                'outward'  => 'outward' . random_int(1, 100000),
                'inward'   => 'inward ' . random_int(1, 100000),
                'editable' => true,

            ]
        );

        // mock calls:
        $repository->shouldReceive('setUser');

        // data to submit
        $data = [
            'name'     => 'random' . random_int(1, 100000),
            'outward'  => 'outward' . random_int(1, 100000),
            'inward'   => 'inward ' . random_int(1, 100000),
            'editable' => true,

        ];

        // test API
        $response = $this->put('/api/v1/link_types/' . $linkType->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('You need the \"owner\"-role to do this.');
    }


}
