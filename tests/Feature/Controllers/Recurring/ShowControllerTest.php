<?php
/**
 * ShowControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;


use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Log;
use Mockery;

/**
 *
 * Class ShowControllerTest
 */
class ShowControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Recurring\IndexController
     */
    public function testShow(): void
    {
        $repository      = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $transformer     = $this->mock(RecurrenceTransformer::class);

        $this->mockDefaultSession();

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id'                     => 5,
                'first_date'             => '2018-01-01',
                'repeat_until'           => null,
                'latest_date'            => null,
                'repetitions' => [
                    [
                        'occurrences' => [
                            '2019-01-01',
                        ],
                    ],
                ],
            ]
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $recurrence = $this->user()->recurrences()->first();
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getTransactions')->andReturn(new Collection)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('recurring.show', [$recurrence->id]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

}