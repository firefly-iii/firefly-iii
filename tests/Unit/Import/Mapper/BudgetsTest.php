<?php
/**
 * BudgetsTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\Budgets;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class BudgetsTest
 */
class BudgetsTest extends TestCase
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
     * @covers \FireflyIII\Import\Mapper\Budgets
     */
    public function testGetMapBasic(): void
    {
        $one        = new Budget;
        $one->id    = 8;
        $one->name  = 'Something';
        $two        = new Budget;
        $two->id    = 4;
        $two->name  = 'Else';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getActiveBudgets')->andReturn($collection)->once();

        $mapper  = new Budgets();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0 => (string)trans('import.map_do_not_map'),
            4 => 'Else',
            8 => 'Something',

        ];
        $this->assertEquals($result, $mapping);
    }

}
