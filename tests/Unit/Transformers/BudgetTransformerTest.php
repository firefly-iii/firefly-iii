<?php
/**
 * BudgetTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Transformers\BudgetTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;


/**
 * Class BudgetTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetTransformerTest extends TestCase
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
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BudgetTransformer
     */
    public function testBasic(): void
    {
        // mocks and prep:
        $this->mock(BudgetRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);

        $parameters  = new ParameterBag;
        $budget      = Budget::first();
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($parameters);

        // mocks
        $opsRepository->shouldReceive('setUser')->once();

        // action
        $result = $transformer->transform($budget);


        $this->assertEquals($budget->id, $result['id']);
        $this->assertEquals((bool)$budget->active, $result['active']);
        $this->assertEquals([], $result['spent']);

    }

    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BudgetTransformer
     */
    public function testSpentArray(): void
    {
        // mocks and prep:
        $this->mock(BudgetRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);

        $parameters = new ParameterBag;

        // set parameters
        $parameters->set('start', new Carbon('2018-01-01'));
        $parameters->set('end', new Carbon('2018-01-31'));

        $budget      = Budget::first();
        $transformer = app(BudgetTransformer::class);
        $transformer->setParameters($parameters);

        // spent data
        $spent = [
            [
                'currency_id'             => 1,
                'currency_code'           => 'AKC',
                'currency_symbol'         => 'x',
                'currency_decimal_places' => 2,
                'amount'                  => 1000,
            ],
        ];

        // mocks
        $opsRepository->shouldReceive('sumExpenses')->atLeast()->once()->andReturn($spent);
        $opsRepository->shouldReceive('setUser')->once();

        // action
        $result = $transformer->transform($budget);

        $this->assertEquals($budget->id, $result['id']);
        $this->assertEquals((bool)$budget->active, $result['active']);
        $this->assertEquals($spent, $result['spent']);

    }
}
