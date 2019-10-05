<?php
/**
 * CategoryControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers\Chart;


use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\Support\TestDataTrait;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryControllerTest extends TestCase
{
    use TestDataTrait;

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
     * @covers \FireflyIII\Api\V1\Controllers\Chart\CategoryController
     */
    public function testOverview(): void
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $noCatRepos =$this->mock(NoCategoryRepositoryInterface::class);
        $opsRepos = $this->mock(OperationsRepositoryInterface::class);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $noCatRepos->shouldReceive('setUser')->atLeast()->once();
        $opsRepos->shouldReceive('setUser')->atLeast()->once();

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->categoryListExpenses());
        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->categoryListIncome());

        $noCatRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->noCategoryListExpenses());
        $noCatRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->noCategoryListIncome());


        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.category.overview') . '?' . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

}
