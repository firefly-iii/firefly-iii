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
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Log;
use Mockery;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 *
 * Class RecurrenceTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurrenceTransformerTest extends TestCase
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
     * @covers \FireflyIII\Transformers\RecurrenceTransformer
     */
    public function testBasic(): void
    {
        $recurrenceRepos = $this->mock(RecurringRepositoryInterface::class);
        $billRepos       = $this->mock(BillRepositoryInterface::class);
        $piggyRepos      = $this->mock(PiggyBankRepositoryInterface::class);
        $factory         = $this->mock(CategoryFactory::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $category        = $this->getRandomCategory();
        $budget          = $this->getRandomBudget();
        $piggy           = $this->getRandomPiggyBank();
        $bill            = $this->getRandomBill();
        $ranges          = [new Carbon];
        $recurrence      = $this->getRandomRecurrence();
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
        $factory->shouldReceive('findOrCreate')->atLeast()->once()->withArgs([null,Mockery::any()])->andReturn($category);
        $budgetRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($budget);
        $piggyRepos->shouldReceive('findNull')->andReturn($piggy);
        $billRepos->shouldReceive('find')->andReturn($bill);

        // basic transformation:

        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $result = $transformer->transform($recurrence);

        $this->assertEquals($recurrence->id, $result['id']);
        //$this->assertEquals('deposit', $result['transaction_type']);
        $this->assertEquals(true, $result['apply_rules']);
        $this->assertEquals('Rep descr', $result['recurrence_repetitions'][0]['description']);


    }

}
