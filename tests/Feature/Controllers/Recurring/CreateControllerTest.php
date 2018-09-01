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


use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
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

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     */
    public function testCreate(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        \Amount::shouldReceive('getDefaultCurrency')->andReturn(TransactionCurrency::find(1));


        $this->be($this->user());
        $response = $this->get(route('recurring.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStore(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello',
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr',
            'active'                  => '1',
            'apply_rules'             => '1',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',
            // mandatory account info:
            'source_id'               => '1',
            'source_name'             => '',
            'destination_id'          => '',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',

            'repetition_end' => 'times',
            'repetitions'    => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}