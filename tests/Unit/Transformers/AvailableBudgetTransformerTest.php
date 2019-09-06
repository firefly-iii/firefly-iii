<?php
/**
 * AvailableBudgetTransformerTest.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class AvailableBudgetTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AvailableBudgetTransformerTest extends TestCase
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
     * Test basic transformer
     *
     * @covers \FireflyIII\Transformers\AvailableBudgetTransformer
     */
    public function testBasic(): void
    {
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $nbRepos       = $this->mock(NoBudgetRepositoryInterface::class);
        $repository->shouldReceive('setUser')->atLeast()->once();

        /** @var AvailableBudget $availableBudget */
        $availableBudget = AvailableBudget::first();
        $currency        = $availableBudget->transactionCurrency;
        // make transformer
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($availableBudget);

        // test results
        $this->assertEquals($availableBudget->id, $result['id']);
        $this->assertEquals($currency->id, $result['currency_id']);
        $this->assertEquals($availableBudget->start_date->format('Y-m-d'), $result['start']);
        $this->assertEquals(round($availableBudget->amount, 2), $result['amount']);
    }

    /**
     * Test basic transformer
     *
     * @covers \FireflyIII\Transformers\AvailableBudgetTransformer
     */
    public function testBasicDates(): void
    {
        $euro = $this->getEuro();
        $data= [
             [
                'currency_id'             => $euro->id,
                'currency_code'           => $euro->code,
                'currency_symbol'         => $euro->symbol,
                'currency_decimal_places' => $euro->decimal_places,
                'amount'                  => '12.45',
            ]
        ];


        $budget        = $this->getRandomBudget();
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $opsRepository = $this->mock(OperationsRepositoryInterface::class);
        $nbRepos       = $this->mock(NoBudgetRepositoryInterface::class);
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $opsRepository->shouldReceive('spentInPeriodMc')->atLeast()->once()->andReturn($data);
        $nbRepos->shouldReceive('spentInPeriodWoBudgetMc')->atLeast()->once()->andReturn($data);

        // spentInPeriodWoBudgetMc

        $start        = new Carbon;
        $end          = new Carbon;
        $parameterBag = new ParameterBag;
        $parameterBag->set('start', $start);
        $parameterBag->set('end', $end);

        /** @var AvailableBudget $availableBudget */
        $availableBudget = AvailableBudget::first();
        $currency        = $availableBudget->transactionCurrency;
        // make transformer
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($parameterBag);
        $result = $transformer->transform($availableBudget);

        // test results
        $this->assertEquals($availableBudget->id, $result['id']);
        $this->assertEquals($currency->id, $result['currency_id']);
        $this->assertEquals($availableBudget->start_date->format('Y-m-d'), $result['start']);
        $this->assertEquals(round($availableBudget->amount, 2), $result['amount']);
    }

}
