<?php
/**
 * BoxControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BoxControllerTest
 */
class BoxControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::available
     */
    public function testAvailable()
    {
        $return     = [
            0 => [
                'spent' => '-1200', // more than budgeted.
            ],
        ];
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::balance
     */
    public function testBalance()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::bills
     */
    public function testBills()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.bills'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::netWorth()
     */
    public function testNetWorth()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController::netWorth()
     */
    public function testNetWorthFuture()
    {
        $start = new Carbon;
        $start->addMonths(6)->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $this->session(['start' => $start, 'end' => $end]);
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }
}
