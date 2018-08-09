<?php
/**
 * OperationsControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Report;

use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Log;
use Tests\TestCase;

/**
 * Class OperationsControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OperationsControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController
     */
    public function testExpenses(): void
    {
        $return = [
            1 => [
                'id'      => 1,
                'name'    => 'Some name',
                'sum'     => '5',
                'average' => '5',
                'count'   => 1,
            ],
        ];
        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('getExpenseReport')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('report-data.operations.expenses', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController
     */
    public function testIncome(): void
    {
        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('getIncomeReport')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('report-data.operations.income', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController
     */
    public function testOperations(): void
    {
        $return = [
            1 => [
                'id'      => 1,
                'name'    => 'Some name',
                'sum'     => '5',
                'average' => '5',
                'count'   => 1,
            ],
        ];

        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('getExpenseReport')->andReturn($return);
        $tasker->shouldReceive('getIncomeReport')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('report-data.operations.operations', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }
}
