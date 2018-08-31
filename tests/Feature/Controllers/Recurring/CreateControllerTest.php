<?php
/**
 * CreateControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;


use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 *
 * Class CreateControllerTest
 */
class CreateControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    public function testCreate() {
        $recurringRepos =$this->mock(RecurringRepositoryInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);

        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        \Amount::shouldReceive('getDefaultCurrency')->andReturn(TransactionCurrency::find(1));


        $this->be($this->user());
        $response = $this->get(route('recurring.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }
}