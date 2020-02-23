<?php
/**
 * SearchControllerTest.php
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

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class SearchControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\SearchController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $search    = $this->mock(SearchInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $search->shouldReceive('parseQuery')->once();
        $search->shouldReceive('getWordsAsString')->once()->andReturn('test');
        $search->shouldReceive('getModifiers')->once()->andReturn(new Collection);
        $this->be($this->user());
        $response = $this->get(route('search.index') . '?q=test');
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\SearchController
     */
    public function testSearch(): void
    {
        $this->mockDefaultSession();
        $search = $this->mock(SearchInterface::class);

        $search->shouldReceive('parseQuery')->once();
        $search->shouldReceive('setLimit')->withArgs([50])->once();
        $search->shouldReceive('searchTransactions')->once()->andReturn(new LengthAwarePaginator([], 0, 10));
        $search->shouldReceive('searchTime')->once()->andReturn(0.2);

        $this->be($this->user());

        $response = $this->get(route('search.search') . '?query=test');
        $response->assertStatus(200);
    }
}
