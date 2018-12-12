<?php
/**
 * ImportControllerTest.php
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
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class ImportControllerTest
 */
class ImportControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\ImportController
     */
    public function testListAll(): void
    {
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('get')->once()->andReturn(new Collection);


        $response = $this->get(route('api.v1.import.list'));
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\ImportController
     */
    public function testShow(): void
    {
        /** @var ImportJob $job */
        $job        = $this->user()->importJobs()->first();
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        $response = $this->get(route('api.v1.import.show', [$job->key]), ['accept' => 'application/json']);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\ImportController
     */
    public function testTransactions(): void
    {
        /** @var ImportJob $job */
        $job         = $this->user()->importJobs()->first();
        $tag         = $this->user()->tags()->first();
        $job->tag_id = $tag->id;
        $job->save();
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        // paginator:
        $paginator = new LengthAwarePaginator(new Collection, 0, 50);

        $collector = $this->mock(TransactionCollectorInterface::class);
        $collector->shouldReceive('setUser')->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->once()->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setTag')->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->once()->andReturn($paginator);
        $collector->shouldReceive('removeFilter')->once()->andReturnSelf();

        $response = $this->get(
            route('api.v1.import.transactions', [$job->key]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']),
            ['accept' => 'application/json']
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

}