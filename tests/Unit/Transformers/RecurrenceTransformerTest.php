<?php
/**
 * RecurrenceTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Carbon\Carbon;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 *
 * Class RecurrenceTransformerTest
 */
class RecurrenceTransformerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     *
     */
    public function testBasic(): void
    {
        $recurrenceRepos = $this->mock(RecurringRepositoryInterface::class);
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $factory         = $this->mock(CategoryFactory::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $category        = Category::first();
        $budget          = Budget::first();
        $piggy           = PiggyBank::first();
        $bill            = Bill::first();
        $foreignCurrency = TransactionCurrency::find(2);
        $ranges          = [new Carbon];
        // mock calls:
        $recurrenceRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $factory->shouldReceive('setUser')->atLeast()->once();
        $budgetRepos->shouldReceive('setUser')->atLeast()->once();

        // default calls:
        $recurrenceRepos->shouldReceive('getNoteText')->once()->andReturn('Hi there');
        $recurrenceRepos->shouldReceive('repetitionDescription')->once()->andReturn('Rep descr');
        $recurrenceRepos->shouldReceive('getXOccurrences')->andReturn($ranges)->atLeast()->once();
        $factory->shouldReceive('findOrCreate')->atLeast()->once()->withArgs([null, 'House'])->andReturn($category);
        $budgetRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([2])->andReturn($budget);
        $piggyRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($piggy);
        $billRepos->shouldReceive('find')->atLeast()->once()->withArgs([1])->andReturn($bill);

        // basic transformation:
        /** @var Recurrence $recurrence */
        $recurrence  = Recurrence::find(1);
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $result = $transformer->transform($recurrence);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('withdrawal', $result['transaction_type']);
        $this->assertEquals(true, $result['apply_rules']);
        $this->assertEquals(
            [
                [
                    'value' => 'auto-generated',
                    'tags'  => ['auto-generated'],
                    'name'  => 'tags',
                ],
                [
                    'name'            => 'piggy_bank_id',
                    'piggy_bank_id'   => 1,
                    'piggy_bank_name' => 'New camera',
                    'value'           => '1',
                ],
                [
                    'bill_id'   => 1,
                    'bill_name' => 'Rent',
                    'name'      => 'bill_id',
                    'value'     => '1',

                ],
            ]
            , $result['meta']
        );

        $this->assertEquals($foreignCurrency->code, $result['transactions'][0]['foreign_currency_code']);
        $this->assertEquals('Rep descr', $result['recurrence_repetitions'][0]['description']);


    }

}
